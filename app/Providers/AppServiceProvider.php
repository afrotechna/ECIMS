<?php

namespace App\Providers;

use App\Services\QuestionGeneration\QuestionGeneratorContract;
use App\Services\QuestionGeneration\TemplateQuestionGenerator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(QuestionGeneratorContract::class, TemplateQuestionGenerator::class);
    }

    public function boot(): void
    {
        $appUrl = (string) config('app.url');
        if (str_starts_with($appUrl, 'https://')) {
            $appHost = parse_url($appUrl, PHP_URL_HOST);
            if ($appHost && request()->getHost() === $appHost) {
                URL::forceRootUrl(rtrim($appUrl, '/'));
                URL::forceScheme('https');
            }
        }

        Paginator::useBootstrapFive();

        Blade::if('canModule', function (string $module, string $action = 'view'): bool {
            $user = auth()->user();

            return $user && $user->canModule($module, $action);
        });
    }
}
