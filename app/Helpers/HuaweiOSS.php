<?php

namespace App\Helpers;

use Obs\ObsClient;
use Obs\ObsException;

class HuaweiOSS
{
    protected $ak;
    protected $sk;
    protected $endpoint;
    protected $bucket_name;
    protected $object_key;

    public function __construct()
    {
        $this->ak = 'MNLRIGI56EQA2TWADHPR';
        $this->sk = 'agMr9pg25yibri7UuGupCtNP59V4tvUQh13EL9FL';
        // $this->endpoint = 'https://obs.ap-southeast-4.myhuaweicloud.com'; //Jakarta
        $this->endpoint = 'https://obs.ap-southeast-3.myhuaweicloud.com'; // Singapore
        // $this->bucket_name = 'simandor-bucket';
        $this->bucket_name = 'mandep';
        $this->object_key = 'asd';
    }

    public function createFolder()
    {
        $obs_client = ObsClient::factory([
            'key' => $this->ak,
            'secret' => $this->sk,
            'endpoint' => $this->endpoint,
            'socket_timeout' => 30,
            'connect_timeout' => 10
        ]);
        try {
            /*
             * Create an empty folder without request body, note that the key must be
             * suffixed with a slash
             */
            $prefix = "MyObjectKey1/";

            $obs_client->putObject(['Bucket' => $this->bucket_name, 'Key' => $prefix]);
            echo "Creating an empty folder " . $prefix . "\n\n";
            
            /*
             * Verify whether the size of the empty folder is zero
             */
            $resp = $obs_client->getObject(['Bucket' => $this->bucket_name, 'Key' => $prefix]);
            
            echo "Size of the empty folder '" . $prefix. "' is " . $resp['ContentLength'] .  "\n\n";

            if($resp['Body']){
                $resp['Body']->close();
            }
            
            /*
             * Create an object under the folder just created
             */
            $obs_client->putObject(['Bucket' => $this->bucket_name, 'Key' => $prefix . $this->object_key, 'Body' => 'Hello OBS']);

        } catch ( ObsException $e ) {
            echo 'Response Code:' . $e->getStatusCode() . PHP_EOL;
            echo 'Error Message:' . $e->getExceptionMessage() . PHP_EOL;
            echo 'Error Code:' . $e->getExceptionCode() . PHP_EOL;
            echo 'Request ID:' . $e->getRequestId() . PHP_EOL;
            echo 'Exception Type:' . $e->getExceptionType() . PHP_EOL;
        } finally {
            $obs_client->close();
        }
    }

    public function putObject($folder, $filename, $file)
    {
        $temp_file_path = $file->getPathName();
        $file_original_name = $file->getClientOriginalName();
        $mime = $file->getClientMimeType();

        $obs_client = ObsClient::factory([
            'key' => $this->ak,
            'secret' => $this->sk,
            'endpoint' => $this->endpoint,
            'socket_timeout' => 30,
            'connect_timeout' => 10
        ]);

        try {
            $resp = $obs_client->putObject([
                'Bucket' => $this->bucket_name,
                'Key' => $folder.'/'.$filename,
                'Metadata' => [
                    'original_filename' => $file_original_name
                ],
                'ContentType' => $mime,
                'SourceFile' => $temp_file_path,
                'ACL' => ObsClient::AclPublicRead
            ]);

            return [
                'status' => 'success',
                'message' => 'Sukses upload gambar',
                'data' => $resp
            ];
        } catch ( ObsException $e ) {
            \Log::error($e);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }


    public function delObject($folder, $filename)
    {
        $obs_client = ObsClient::factory([
            'key' => $this->ak,
            'secret' => $this->sk,
            'endpoint' => $this->endpoint,
            'socket_timeout' => 30,
            'connect_timeout' => 10
        ]);

        try {
            $resp = $obs_client->deleteObjects([
                'Bucket' => $this->bucket_name,
                'Objects' => [
                    ['Key' => $folder.$filename]
                ],
                'Quiet'=> false,
            ]);
            // $resp = $obs_client->putObject([
            //     'Bucket' => $this->bucket_name,
            //     'Key' => $folder.$filename,
            //     'Metadata' => [
            //         'original_filename' => $file_original_name
            //     ],
            //     'ContentType' => $mime,
            //     'SourceFile' => $temp_file_path,
            //     'ACL' => ObsClient::AclPublicRead
            // ]);

            return [
                'status' => 'success',
                'message' => 'Sukses hapus gambar',
                'data' => $resp
            ];
        } catch ( ObsException $e ) {
            \Log::error($e);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function deleteObject(string $key)
    {
        $obs_client = ObsClient::factory([
            'key' => $this->ak,
            'secret' => $this->sk,
            'endpoint' => $this->endpoint,
            'socket_timeout' => 30,
            'connect_timeout' => 10
        ]);

        try {
            $obs_client->deleteObject([
                'Bucket' => $this->bucket_name,
                'Key' => $key,
            ]);

            return [
                'status' => 'success',
                'message' => 'File deleted successfully',
            ];
        } catch (ObsException $e) {
            \Log::error($e);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function updateObject($folder, $filename, $file): array
    {
        $deleteResult = $this->deleteObject($folder . $filename);
        if ($deleteResult['status'] !== 'success') {
            return $deleteResult;
        }
    
        return $this->putObject($folder, $filename, $file);
    }

    public function downloadObject($path)
    {
        $obs_client = ObsClient::factory([
            'key' => $this->ak,
            'secret' => $this->sk,
            'endpoint' => $this->endpoint,
            'socket_timeout' => 30,
            'connect_timeout' => 10
        ]);

        $resp = $obs_client->getObject(['Bucket' => $this->bucket_name, 'Key' => $path]);

        // And a path where the file will be created
        $path = storage_path('app/public').'/asdasdasd.pdf';

        // Then just save it like this
        file_put_contents( $path, $resp['Body'] );

        printf("\t%s\n\n", $resp['Body']);

        dd($resp);
    }

    // deleteObject
    // VersionId
}