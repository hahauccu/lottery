<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = '主控台';

    protected static ?string $title = '主控台';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?int $navigationSort = -2;

    protected static string $routePath = '/';
}
