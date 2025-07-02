<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Repository\GitHub;

use Spiral\RoadRunner\Console\Repository\ReleasesCollection;
use Spiral\RoadRunner\Console\Repository\RepositoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @psalm-import-type GitHubReleaseApiResponse from GitHubRelease
 */
final class GitHubRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    private const URL_RELEASES = 'https://api.github.com/repos/%s/releases';

    private HttpClientInterface $client;
    private string $name;

    /**
     * @var array|string[]
     */
    private array $headers = [
        'accept' => 'application/vnd.github.v3+json',
    ];

    public function __construct(string $owner, string $repository, ?HttpClientInterface $client = null)
    {
        $this->name = $owner . '/' . $repository;
        $this->client = $client ?? HttpClient::create();
    }

    public static function create(string $owner, string $name, ?HttpClientInterface $client = null): GitHubRepository
    {
        return new GitHubRepository($owner, $name, $client);
    }

    /**
     *
     * @throws ExceptionInterface
     */
    public function getReleases(): ReleasesCollection
    {
        return ReleasesCollection::from(function () {
            $page = 0;

            do {
                $response = $this->releasesRequest(++$page);

                /** @psalm-var GitHubReleaseApiResponse $data */
                foreach ($response->toArray() as $data) {
                    yield GitHubRelease::fromApiResponse($this, $this->client, $data);
                }
            } while ($this->hasNextPage($response));
        });
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws TransportExceptionInterface
     * @see HttpClientInterface::request()
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        // Merge headers with defaults
        $options['headers'] = \array_merge($this->headers, (array) ($options['headers'] ?? []));

        return $this->client->request($method, $uri, $options);
    }

    /**
     * @param positive-int $page
     * @throws TransportExceptionInterface
     */
    private function releasesRequest(int $page): ResponseInterface
    {
        return $this->request('GET', $this->uri(self::URL_RELEASES), [
            'query' => [
                'page' => $page,
            ],
        ]);
    }

    private function uri(string $pattern): string
    {
        return \sprintf($pattern, $this->getName());
    }

    /**
     * @throws ExceptionInterface
     */
    private function hasNextPage(ResponseInterface $response): bool
    {
        $headers = $response->getHeaders();
        $link = $headers['link'] ?? [];

        if (! isset($link[0])) {
            return false;
        }

        return \str_contains($link[0], 'rel="next"');
    }
}
