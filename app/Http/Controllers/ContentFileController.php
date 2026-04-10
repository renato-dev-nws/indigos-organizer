<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentFileRequest;
use App\Models\Content;
use App\Models\ContentFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ContentFileController extends Controller
{
    public function store(StoreContentFileRequest $request, Content $content): RedirectResponse
    {
        $this->authorize('update', $content);

        $file = $request->file('file');
        $path = $file->store('contents/'.$content->id, 'local');

        $content->files()->create([
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('success', 'Arquivo enviado com sucesso.');
    }

    public function destroy(Content $content, ContentFile $file): RedirectResponse
    {
        $this->authorize('update', $content);
        abort_unless($file->content_id === $content->id, 404);

        Storage::disk('local')->delete($file->path);
        $file->delete();

        return back()->with('success', 'Arquivo removido com sucesso.');
    }
}
