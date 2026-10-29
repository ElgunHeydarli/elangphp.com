<?php
declare(strict_types=1);
namespace App\Seo;
use Elang\Support\Config;
final class PublicAnalytics
{
    public function __construct(private Config $config) {}
    public function settings(): array
    {
        $measurement=(string)$this->config->get('site_analytics.measurement_id','');
        $verification=(string)$this->config->get('site_analytics.verification','');
        return [
            'google_measurement_id'=>preg_match('/^G-[A-Z0-9]{6,20}$/D',$measurement)?$measurement:'',
            'google_site_verification'=>preg_match('/^[A-Za-z0-9_-]{20,128}$/D',$verification)?$verification:'',
        ];
    }
    public static function contentSecurityPolicy(): string
    {
        return "default-src 'self'; script-src 'self' https://www.googletagmanager.com; style-src 'self'; img-src 'self' data: https://*.google-analytics.com https://*.googletagmanager.com; connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com; font-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'";
    }
}
