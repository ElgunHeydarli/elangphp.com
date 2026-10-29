<?php
declare(strict_types=1);
namespace Elang\Observability;
use Elang\{Support\Config,Application};
use OpenTelemetry\API\Trace\{Propagation\TraceContextPropagator,SpanKind,StatusCode,SpanInterface};
use OpenTelemetry\SDK\Trace\{TracerProvider,SpanProcessor\BatchSpanProcessor,Sampler\ParentBased,Sampler\TraceIdRatioBasedSampler};
use Symfony\Component\HttpFoundation\{Request,Response};
final class Telemetry
{
    private ?\OpenTelemetry\SDK\Trace\TracerProviderInterface $provider=null;
    public function __construct(private Config $config,?\OpenTelemetry\SDK\Trace\TracerProviderInterface $provider=null) { $this->provider=$provider; }
    public function enabled():bool { return $this->config->get('telemetry.enabled',false)===true; }
    private function provider():\OpenTelemetry\SDK\Trace\TracerProviderInterface
    {
        if($this->provider) return $this->provider;$endpoint=$this->config->get('telemetry.endpoint','http://127.0.0.1:4318/v1/traces');
        if(!str_starts_with($endpoint,'https://')&&!preg_match('~^http://(localhost|127\.0\.0\.1|\[::1\]|otel-collector):[0-9]+/~',$endpoint)) throw new \InvalidArgumentException('Use HTTPS or a local OpenTelemetry collector.');
        $rate=$this->config->get('telemetry.sample_rate',0.1);if(!is_numeric($rate)||(float)$rate<0||(float)$rate>1) throw new \InvalidArgumentException('Trace sampling must be 0–1.');
        $transport=(new \OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory())->create($endpoint,'application/x-protobuf',timeout:0.25,maxRetries:0);
        $resource=\OpenTelemetry\SDK\Resource\ResourceInfo::create(\OpenTelemetry\SDK\Common\Attribute\Attributes::create(['service.name'=>$this->config->get('telemetry.service_name','elang'),'service.version'=>Application::VERSION,'deployment.environment.name'=>$this->config->get('environment')]));
        $this->provider=TracerProvider::builder()->setResource($resource)->setSampler(new ParentBased(new TraceIdRatioBasedSampler((float)$rate)))->addSpanProcessor(BatchSpanProcessor::builder(new \OpenTelemetry\Contrib\Otlp\SpanExporter($transport))->build())->build();
        register_shutdown_function(function():void { try { $this->provider?->shutdown(); }catch(\Throwable) {} });return $this->provider;
    }
    /** @param 0|1|2|3|4 $kind */
    private function start(string $name,int $kind,array $carrier=[]):?SpanInterface
    {
        if(!$this->enabled()) return null;
        try { $builder=$this->provider()->getTracer('elang.framework',Application::VERSION)->spanBuilder($name)->setSpanKind($kind);if($carrier) $builder->setParent(TraceContextPropagator::getInstance()->extract($carrier));return $builder->startSpan(); }catch(\Throwable) { return null; }
    }
    public function http(Request $request,callable $callback):Response
    {
        $carrier=[];foreach(['traceparent','tracestate'] as $key) { $value=$request->headers->get($key);if(is_string($value)&&strlen($value)<=512) $carrier[$key]=$value; }$span=$this->start('HTTP '.$request->getMethod(),SpanKind::KIND_SERVER,$carrier);$scope=$span?->activate();
        try {
            $response=$callback();if($span) { $route=$request->attributes->get('_route');$label=is_string($route)?$route:'unmatched';$span->updateName($request->getMethod().' '.$label);$span->setAttribute('http.request.method',$request->getMethod());$span->setAttribute('http.route',$label);$span->setAttribute('http.response.status_code',$response->getStatusCode());if($response->getStatusCode()>=500) $span->setStatus(StatusCode::STATUS_ERROR); }return $response;
        }catch(\Throwable $e) { $span?->setStatus(StatusCode::STATUS_ERROR);$span?->setAttribute('error.type',$e::class);throw $e; }finally { $scope?->detach();$span?->end(); }
    }
    public function carrier():array { $carrier=[];if($this->enabled()) TraceContextPropagator::getInstance()->inject($carrier);return $carrier; }
    public function job(string $name,array $carrier,callable $callback):mixed
    {
        $span=$this->start('job '.$name,SpanKind::KIND_CONSUMER,$carrier);$scope=$span?->activate();try { return $callback(); }catch(\Throwable $e) { $span?->setStatus(StatusCode::STATUS_ERROR);$span?->setAttribute('error.type',$e::class);throw $e; }finally { $scope?->detach();$span?->end(); }
    }
    public function flush():void { try { $this->provider?->forceFlush(); }catch(\Throwable) {} }
}
