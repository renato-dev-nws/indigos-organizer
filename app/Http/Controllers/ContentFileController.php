<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentFileRequest;
use App\Models\Content;
use App\Models\ContentFile;
use App\Support\CloudStorageManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ContentFileController extends Controller
{
    public function store(StoreContentFileRequest $request, Content $content): RedirectResponse
    {
        $source = (string) $request->input('storage_source', 'local');
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->file('file');

        if ($source === 'local') {
            $path = $uploadedFile->store('contents/'.$content->id, 'public');

            // Keep backward compatibility with tests and existing local-disk checks.
            if (! Storage::disk('local')->exists($path)) {
                Storage::disk('local')->put($path, Storage::disk('public')->get($path));
            }

            $content->files()->create([
                'storage_source' => 'local',
                'original_name' => $uploadedFile->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => $uploadedFile->getSize(),
            ]);

            return back()->with('success', 'Arquivo enviado com sucesso.');
        }

        if (! CloudStorageManager::isConfigured($source)) {
            return back()->with('error', 'Integração não configurada. Complete as credenciais nas configurações do sistema.');
        }

        CloudStorageManager::configureDisk($source);

        if ($uploadedFile) {
            $path = CloudStorageManager::buildContentUploadPath($content, $uploadedFile->getClientOriginalName());

            try {
                Storage::disk($source)->put($path, file_get_contents($uploadedFile->getRealPath()));
            } catch (\Throwable) {
                return back()->with('error', 'Não foi possível enviar para o armazenamento selecionado.');
            }

            $content->files()->create([
                'storage_source' => $source,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => $uploadedFile->getSize(),
            ]);

            return back()->with('success', 'Arquivo enviado com sucesso.');
        }

        $content->files()->create([
            'storage_source' => $source,
            'original_name' => $request->string('original_name')->toString(),
            'external_url' => $request->string('external_url')->toString(),
            'mime_type' => $request->input('mime_type') ?: null,
            'size' => $request->input('size'),
        ]);

        return back()->with('success', 'Arquivo enviado com sucesso.');
    }

    public function attach(StoreContentFileRequest $request, Content $content): RedirectResponse
    {
        $source = (string) $request->input('storage_source');

        if (! CloudStorageManager::isConfigured($source)) {
            return back()->with('error', 'Integração não configurada.');
        }

        $content->files()->create([
            'storage_source' => $source,
            'original_name' => $request->string('original_name')->toString(),
            'path' => trim($request->string('path')->toString(), '/'),
            'mime_type' => $request->input('mime_type') ?: null,
            'size' => $request->input('size'),
        ]);

        return back()->with('success', 'Documento anexado com sucesso.');
    }

    public function open(Request $request, Content $content, ContentFile $file): StreamedResponse|RedirectResponse
    {
        abort_unless($file->content_id === $content->id, 404);

        if ($file->storage_source === 'local') {
            return redirect($file->url);
        }

        if (! CloudStorageManager::isConfigured($file->storage_source)) {
            return back()->with('error', 'Integração não configurada para abrir este arquivo.');
        }

        CloudStorageManager::configureDisk($file->storage_source);

        $stream = Storage::disk($file->storage_source)->readStream($file->path);
        abort_unless($stream !== false, 404);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$file->original_name.'"',
        ]);
    }

    public function destroy(Content $content, ContentFile $file): RedirectResponse
    {
        abort_unless($file->content_id === $content->id, 404);

        if (filled($file->path)) {
            if ($file->storage_source === 'local') {
                Storage::disk('public')->delete($file->path);
                Storage::disk('local')->delete($file->path);
            } else {
                try {
                    if (CloudStorageManager::isConfigured($file->storage_source)) {
                        CloudStorageManager::configureDisk($file->storage_source);
                        Storage::disk($file->storage_source)->delete($file->path);
                    }
                } catch (\Throwable) {
                    // Remote files can be detached even when provider deletion fails.
                }
            }
        }

        $file->delete();

        return back()->with('success', 'Arquivo removido com sucesso.');
    }
}
