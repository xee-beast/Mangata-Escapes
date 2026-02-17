<?php

namespace App\Http\Controllers;

use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $faqs = Faq::query();

        $search = $request->query('search', '');
        
        if (!empty($search)) {
            $faqs = $faqs->where('title', 'like', "%{$search}%");
        }
        
        $faqs->orderBy('id', 'desc');

        return FaqResource::collection($faqs->paginate($request->query('paginate', 10)))
            ->additional([
                'can' => [
                    'create' => auth()->user()->can('manage faqs'),
                ]
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:1000',
            'description' => 'required|string|max:10000',
        ]);

        $faq = new Faq();
        $faq->title = $request->input('title');
        $faq->description = $request->input('description');
        $faq->type = 'static';
        $faq->save();
        
        return (new FaqResource($faq))->response()->setStatusCode(201);
    }

    public function update(Request $request, Faq $faq)
    {
        $request->validate([
            'title' => 'required|string|max:1000',
            'description' => 'required|string|max:10000',
        ]);

        $faq->title = $request->input('title');
        $faq->description = $request->input('description');
        $faq->save();

        return (new FaqResource($faq));
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return response()->json()->setStatusCode(204);
    }
}
