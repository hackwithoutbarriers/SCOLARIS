<?php

namespace App\Services\Notifications;

use App\Models\Guardian;
use App\Models\MessageTemplate;
use RuntimeException;

class TemplateRenderer
{
    public function template(Guardian $guardian, string $code, string $channel): MessageTemplate
    {
        $template = MessageTemplate::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->whereIn('channel', [$channel, 'both'])
            ->where(function ($query) use ($guardian): void {
                $query->where('school_id', $guardian->school_id)->orWhereNull('school_id');
            })
            ->orderByRaw('CASE WHEN school_id IS NULL THEN 1 ELSE 0 END')
            ->first();
        if (! $template) {
            throw new RuntimeException("Template de notification introuvable: {$code}");
        }

        return $template;
    }

    public function render(string $body, array $variables): string
    {
        $rendered = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $match) use ($variables): string {
            if (! array_key_exists($match[1], $variables) || $variables[$match[1]] === null) {
                throw new RuntimeException("Variable de template manquante: {$match[1]}");
            }

            return (string) $variables[$match[1]];
        }, $body);
        if ($rendered === null || preg_match('/\{\{.*?\}\}/', $rendered)) {
            throw new RuntimeException('Le message contient encore un placeholder non résolu.');
        }

        return $rendered;
    }
}
