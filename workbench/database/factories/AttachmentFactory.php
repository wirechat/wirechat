<?php

namespace Namu\WireChat\Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Namu\WireChat\Models\Attachment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Attachment::class;

    public function definition(): array
    {
        return [
            'file_path' => '/fake/path',
            'file_name' => 'file.png',
            'original_name' => 'file',
            'mime_type' => 'image/png',
            'url' => 'test.example.com/attachments/file.png',
        ];
    }
}
