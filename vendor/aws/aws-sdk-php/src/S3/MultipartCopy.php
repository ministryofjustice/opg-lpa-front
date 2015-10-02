<?php
namespace Aws\S3;

use Aws\Multipart\AbstractUploadManager;
use Aws\ResultInterface;
use GuzzleHttp\Psr7;

class MultipartCopy extends AbstractUploadManager
{
    use MultipartUploadingTrait;

    /** @var string */
    private $source;
    /** @var ResultInterface */
    private $sourceMetadata;

    /**
     * Creates a multipart upload for copying an S3 object.
     *
     * The valid configuration options are as follows:
     *
     * - acl: (string) ACL to set on the object being upload. Objects are
     *   private by default.
     * - before_complete: (callable) Callback to invoke before the
     *   `CompleteMultipartUpload` operation. The callback should have a
     *   function signature like `function (Aws\Command $command) {...}`.
     * - before_initiate: (callable) Callback to invoke before the
     *   `CreateMultipartUpload` operation. The callback should have a function
     *   signature like `function (Aws\Command $command) {...}`.
     * - before_upload: (callable) Callback to invoke before `UploadPartCopy`
     *   operations. The callback should have a function signature like
     *   `function (Aws\Command $command) {...}`.
     * - bucket: (string, required) Name of the bucket to which the object is
     *   being uploaded.
     * - concurrency: (int, default=int(5)) Maximum number of concurrent
     *   `UploadPart` operations allowed during the multipart upload.
     * - key: (string, required) Key to use for the object being uploaded.
     * - part_size: (int, default=int(5242880)) Part size, in bytes, to use when
     *   doing a multipart upload. This must between 5 MB and 5 GB, inclusive.
     * - state: (Aws\Multipart\UploadState) An object that represents the state
     *   of the multipart upload and that is used to resume a previous upload.
     *   When this option is provided, the `bucket`, `key`, and `part_size`
     *   options are ignored.
     * - source_metadata: (Aws\ResultInterface) An object that represents the
     *   result of executing a HeadObject command on the copy source.
     *
     * @param S3Client $client Client used for the upload.
     * @param string   $source Location of the data to be copied
     *                          (in the form /<bucket>/<key>).
     * @param array    $config Configuration used to perform the upload.
     */
    public function __construct(S3Client $client, $source, array $config = [])
    {
        $this->source = '/' . ltrim($source, '/');
        parent::__construct($client, array_change_key_case($config) + [
            'source_metadata' => null
        ]);
    }

    protected function loadUploadWorkflowInfo()
    {
        return [
            'command' => [
                'initiate' => 'CreateMultipartUpload',
                'upload'   => 'UploadPartCopy',
                'complete' => 'CompleteMultipartUpload',
            ],
            'id' => [
                'bucket'    => 'Bucket',
                'key'       => 'Key',
                'upload_id' => 'UploadId',
            ],
            'part_num' => 'PartNumber',
        ];
    }

    protected function getUploadCommands(callable $resultHandler)
    {
        $parts = ceil($this->getSourceSize() / $this->determinePartSize());

        for ($partNumber = 1; $partNumber <= $parts; $partNumber++) {
            // If we haven't already uploaded this part, yield a new part.
            if (!$this->state->hasPartBeenUploaded($partNumber)) {
                $command = $this->client->getCommand(
                    $this->info['command']['upload'],
                    $this->createPart($partNumber, $parts)
                        + $this->getState()->getId()
                );
                $command->getHandlerList()->appendSign($resultHandler, 'mup');
                yield $command;
            }
        }
    }

    private function createPart($partNumber, $partsCount)
    {
        $defaultPartSize = $this->determinePartSize();
        $startByte = $defaultPartSize * ($partNumber - 1);
        $partSize = $partNumber < $partsCount
            ? $defaultPartSize
            : $this->getSourceSize() % $defaultPartSize;
        $endByte = $startByte + $partSize;

        return [
            'ContentLength' => $partSize,
            'CopySource' => $this->source,
            'CopySourceRange' => "bytes=$startByte-$endByte",
            'PartNumber' => $partNumber,
        ];
    }

    protected function extractETag(ResultInterface $result)
    {
        return $result->search('CopyPartResult.ETag');
    }

    protected function getSourceMimeType()
    {
        return $this->getSourceMetadata()['ContentType'];
    }

    protected function getSourceSize()
    {
        return $this->getSourceMetadata()['ContentLength'];
    }

    private function getSourceMetadata()
    {
        if (empty($this->sourceMetadata)) {
            $this->sourceMetadata = $this->fetchSourceMetadata();
        }

        return $this->sourceMetadata;
    }

    private function fetchSourceMetadata()
    {
        if ($this->config['source_metadata'] instanceof ResultInterface) {
            return $this->config['source_metadata'];
        }

        list($bucket, $key) = explode('/', ltrim($this->source, '/'), 2);
        return $this->client->headObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
    }
}
