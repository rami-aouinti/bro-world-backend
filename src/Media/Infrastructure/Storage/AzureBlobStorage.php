<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\Storage;

use App\Media\Application\Storage\MediaStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpClient\Response\ResponseInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

use function ltrim;
use function rtrim;
use function sprintf;

/**
 * @package App\Media
 */
class AzureBlobStorage implements MediaStorageInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $azureBlobEndpoint,
        private readonly string $azureBlobContainer,
        private readonly string $azureBlobSasToken,
        private readonly string $azureBlobApiVersion,
    ) {
    }

    public function delete(string $path): void
    {
        $url = $this->buildBlobUrl($path);

        try {
            $response = $this->httpClient->request('DELETE', $url, [
                'headers' => [
                    'x-ms-version' => $this->azureBlobApiVersion,
                ],
                'timeout' => 10,
            ]);

            $this->assertSuccessful($response, $url);
        } catch (Throwable $exception) {
            $this->logger->error('Failed to delete blob from Azure.', [
                'blob_path' => $path,
                'url' => $url,
                'exception' => $exception->getMessage(),
            ]);

            throw new ServiceUnavailableHttpException(null, 'Unable to delete blob from Azure storage.', $exception);
        }
    }

    private function buildBlobUrl(string $path): string
    {
        $base = rtrim($this->azureBlobEndpoint, '/');
        $container = ltrim($this->azureBlobContainer, '/');
        $blobPath = ltrim($path, '/');
        $sas = $this->azureBlobSasToken !== '' ? $this->azureBlobSasToken : '';
        $separator = $sas !== '' && $sas[0] !== '?' ? '?' : '';

        return sprintf('%s/%s/%s%s%s', $base, $container, $blobPath, $separator, $sas);
    }

    private function assertSuccessful(ResponseInterface $response, string $url): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 200 && $statusCode < 300) {
            return;
        }

        $body = null;

        try {
            $body = $response->getContent(false);
        } catch (TransportExceptionInterface) {
            // ignore body fetch errors
        }

        $this->logger->error('Azure Blob deletion responded with non-success status.', [
            'status_code' => $statusCode,
            'url' => $url,
            'body' => $body,
        ]);

        throw new ServiceUnavailableHttpException(null, 'Azure blob deletion failed with status ' . $statusCode);
    }
}
