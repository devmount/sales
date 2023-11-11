<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Models\Project;
use App\Models\Setting;
use App\Filament\Resources\ProjectResource;
use Filament\Resources\Pages\Page;

class DownloadQuote extends Page
{
    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.download-quote';

    public $record;
    public $settings;

    public function mount(Project $record)
    {
        $this->record = $record;
        $this->settings = Setting::pluck('value', 'key');
    }
}
