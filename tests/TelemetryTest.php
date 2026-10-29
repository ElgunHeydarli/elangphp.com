<?php
declare(strict_types=1);
namespace Tests;
use Elang\{Observability\Telemetry,Database\Database,Queue\Jobs,Queue\Queue};
use OpenTelemetry\SDK\Trace\{TracerProvider,SpanProcessor\SimpleSpanProcessor,SpanExporter\InMemoryExporter};
final class TelemetryTest extends TestCase
{
    public function testRealSdkPropagatesTraceIntoQueueAndExcludesRequestSecrets():void
    {
        $app=$this->application(['key'=>base64_encode(random_bytes(32)),'telemetry'=>['enabled'=>true]]);$exporter=new InMemoryExporter();$provider=TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($exporter))->build();$telemetry=new Telemetry($app->config,$provider);$app->container->instance(Telemetry::class,$telemetry);$db=$app->container->get(Database::class)->connection();\Elang\Queue\Schema::up($db);$app->container->get(Jobs::class)->register('report.create',fn()=>null);$app->router->get('/reports/{private}',function()use($app):array { $app->container->get(Queue::class)->push('report.create',['id'=>4]);return ['ok'=>true]; },'reports.create');
        $request=\Symfony\Component\HttpFoundation\Request::create('/reports/secret-value?email=private@example.test');$request->headers->set('Authorization','Bearer private-token');self::assertSame(200,$app->handle($request)->getStatusCode());$trace=json_decode($db->fetchOne('SELECT trace_context FROM elang_jobs'),true);self::assertMatchesRegularExpression('/^00-[a-f0-9]{32}-[a-f0-9]{16}-01$/',$trace['traceparent']);self::assertTrue($app->container->get(Queue::class)->workOne());$telemetry->flush();$spans=$exporter->getSpans();self::assertCount(2,$spans);$one=$spans[0]->getContext();$two=$spans[1]->getContext();self::assertSame($one->getTraceId(),$two->getTraceId());$serialized=[];foreach($spans as $span) $serialized[]=[$span->getName(),$span->getAttributes()->toArray()];$json=json_encode($serialized);self::assertStringNotContainsString('private-token',$json);self::assertStringNotContainsString('private@example',$json);self::assertStringNotContainsString('secret-value',$json);self::assertStringContainsString('reports.create',$json);$provider->shutdown();
    }
}
