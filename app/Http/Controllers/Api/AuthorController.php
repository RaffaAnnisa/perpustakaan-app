<?php

namespace App\Http\Controllers\Api;

use App\Models\Author;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Author::withCount('books')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:authors,email',
            'bio'   => 'nullable|string',
        ]);

        return response()->json(Author::create($validated), 201);
    }

    public function show(Author $author)
    {
       return response()->json($author->load('books')); 
    }

    public function update(Request $request, Author $author)
    {
       $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('authors', 'email')->ignore($author->id)],
            'bio'   => 'nullable|string',
        ]);

        $author->update($validated);

        return response()->json($author); 
    }

    public function destroy(Request $request, Author $author)
    {
       $jumlahBuku = $author->books()->count();

        // Konfirmasi: kalau punya buku, harus kirim confirm=true
        if ($jumlahBuku > 0 && !$request->boolean('confirm')) {
            return response()->json([
                'message' => "Penulis ini memiliki $jumlahBuku buku. Semua buku terkait juga akan terhapus. Kirim confirm=true untuk melanjutkan.",
                'books_count' => $jumlahBuku,
            ], 409);
        }

        DB::transaction(function () use ($author) {
            $author->books()->delete();
            $author->delete();
        });

        return response()->json(['message' => 'Penulis dan buku terkait berhasil dihapus']); 
    }
}
