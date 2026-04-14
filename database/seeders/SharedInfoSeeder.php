<?php

namespace Database\Seeders;

use App\Models\SharedInfo;
use App\Models\SharedInfoCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class SharedInfoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();
        $categories = SharedInfoCategory::query()->where('user_id', $user->id)->get()->keyBy('name');

        $items = [
            [
                'title' => 'Documentos necessários para show',
                'description' => 'Checklist de documentos que a banda precisa levar para apresentações e contratação.',
                'categories' => ['Documentação', 'Produção'],
                'links' => [
                    ['title' => 'Checklist online', 'url' => 'https://example.com/checklist-show', 'description' => 'Versão online atualizada'],
                ],
            ],
            [
                'title' => 'Documentos necessários para editais',
                'description' => 'Materiais recorrentes para inscrição em editais culturais e festivais.',
                'categories' => ['Documentação', 'Editais'],
                'links' => [
                    ['title' => 'Modelo de release', 'url' => 'https://example.com/release-banda', 'description' => 'Texto-base institucional'],
                ],
            ],
        ];

        foreach ($items as $item) {
            $info = SharedInfo::updateOrCreate(
                ['user_id' => $user->id, 'title' => $item['title']],
                ['description' => $item['description']]
            );

            $info->categories()->sync(
                collect($item['categories'])
                    ->map(fn (string $name) => $categories->get($name)?->id)
                    ->filter()
                    ->values()
                    ->all()
            );

            $info->links()->delete();
            foreach ($item['links'] as $link) {
                $info->links()->create($link);
            }
        }
    }
}