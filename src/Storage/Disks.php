<?php
declare(strict_types=1);
namespace Elang\Storage;
use Elang\Support\Config;
use League\Flysystem\{Filesystem,FilesystemOperator};
final class Disks
{
    private array $disks=[];
    public function __construct(private Config $config) {}
    public function get(string $name):FilesystemOperator
    {
        if(isset($this->disks[$name])) return $this->disks[$name];
        $adapter=match($name) {
            'local'=>new \League\Flysystem\Local\LocalFilesystemAdapter($this->config->get('files.local_root',$this->config->get('paths.storage').'/files'),\League\Flysystem\UnixVisibility\PortableVisibilityConverter::fromArray(['file'=>['public'=>0600,'private'=>0600],'dir'=>['public'=>0700,'private'=>0700]])),
            's3'=>$this->s3(),default=>throw new \InvalidArgumentException('Unknown file disk.'),
        };return $this->disks[$name]=new Filesystem($adapter);
    }
    private function s3():\League\Flysystem\AwsS3V3\AwsS3V3Adapter
    {
        $options=['version'=>'latest','region'=>$this->config->get('files.s3.region','us-east-1'),'use_path_style_endpoint'=>$this->config->get('files.s3.path_style',false),'http'=>['connect_timeout'=>2,'timeout'=>10],'retries'=>2];
        $endpoint=$this->config->get('files.s3.endpoint','');if($endpoint!=='') { if(!str_starts_with($endpoint,'https://')&&!($this->config->get('environment')!=='production'&&preg_match('~^http://(localhost|127\.0\.0\.1|\[::1\]|minio|seaweed)(:|/)~',$endpoint))) throw new \InvalidArgumentException('Use an HTTPS S3 endpoint in production.');$options['endpoint']=$endpoint; }
        $key=$this->config->get('files.s3.key','');if($key!=='') $options['credentials']=['key'=>$key,'secret'=>$this->config->get('files.s3.secret','')];$bucket=$this->config->get('files.s3.bucket','');if(!is_string($bucket)||$bucket==='') throw new \RuntimeException('Configure an S3 bucket before using this disk.');
        return new \League\Flysystem\AwsS3V3\AwsS3V3Adapter(new \Aws\S3\S3Client($options),$bucket,$this->config->get('files.s3.prefix','elang'));
    }
}
