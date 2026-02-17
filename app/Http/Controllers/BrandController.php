<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::query();

        $search = $request->query('search', '');
        
        if (!empty($search)) {
            $brands = $brands->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
        }
        
        $brands->orderBy('name', 'asc');

        return BrandResource::collection($brands->paginate($request->query('paginate', 10)))
            ->additional([
                'can' => [
                    'create' => auth()->user()->can('manage brands'),
                ],
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:brands,name|max:255',
            'concessions' => 'required|string|max:10000',
        ]);

        $brand = new Brand();
        $brand->name = $request->input('name');
        $brand->concessions = $request->input('concessions');
        $brand->save();
        
        return (new BrandResource($brand))->response()->setStatusCode(201);
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name' => 'required|string|unique:brands,name,' . $brand->id . '|max:255',
            'concessions' => 'required|string|max:10000',
        ]);

        $brand->name = $request->input('name');
        $brand->concessions = $request->input('concessions');
        $brand->save();

        return (new BrandResource($brand));
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->json()->setStatusCode(204);
    }
}
