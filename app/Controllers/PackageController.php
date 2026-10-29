<?php
declare(strict_types=1);
namespace App\Controllers;
use Elang\Application;
use Elang\Http\HttpException;
use Symfony\Component\HttpFoundation\{Response, BinaryFileResponse};
final class PackageController
{
    public function __construct(private Application $app) {}
    public function index(): Response
    {
        $file = $this->app->basePath.'/storage/distribution/packages.json';
        if (!is_file($file)) throw new HttpException(503, 'The Composer repository is being prepared. Please try again later.');
        return new Response(file_get_contents($file), 200, ['Content-Type' => 'application/json', 'Cache-Control' => 'public, max-age=0, must-revalidate', 'X-Robots-Tag' => 'noindex']);
    }
    public function dist(string $file): Response
    {
        if (!preg_match('/^elang-(framework|project|task-board|installer)-[0-9][a-z0-9.-]*-[a-f0-9]{40}\.zip$/D', $file)) throw new HttpException(404, 'Package not found.');
        $path = $this->app->basePath.'/storage/distribution/dist/'.$file;
        if (!is_file($path)) throw new HttpException(404, 'Package not found.');
        return new BinaryFileResponse($path, 200, ['Content-Type' => 'application/zip', 'Cache-Control' => 'public, max-age=31536000, immutable', 'X-Robots-Tag' => 'noindex']);
    }
}
