<?php
declare(strict_types=1);
if(getenv('FILES_DISK')!=='s3') exit(0);require dirname(__DIR__).'/vendor/autoload.php';
$client=new Aws\S3\S3Client(['version'=>'latest','region'=>getenv('S3_REGION')?:'us-east-1','endpoint'=>getenv('S3_ENDPOINT'),'use_path_style_endpoint'=>true,'credentials'=>['key'=>getenv('S3_KEY'),'secret'=>getenv('S3_SECRET')],'http'=>['connect_timeout'=>1,'timeout'=>3],'retries'=>0]);
for($attempt=0;$attempt<30;$attempt++) { try { $client->createBucket(['Bucket'=>getenv('S3_BUCKET')]);echo "Development S3 bucket ready.\n";exit(0); }catch(Aws\S3\Exception\S3Exception $error) { if(in_array($error->getAwsErrorCode(),['BucketAlreadyExists','BucketAlreadyOwnedByYou'],true)) exit(0); }catch(Throwable) {}sleep(1); }
fwrite(STDERR,"Development S3 setup failed. Check the private service and credentials.\n");exit(1);
