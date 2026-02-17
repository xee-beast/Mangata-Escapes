<?php

namespace App\Observers;

use App\Models\Faq;
use App\Models\Group;
use App\Models\Image;

class GroupObserver
{
    /**
     * Handle the group "created" event.
     *
     * @param  \App\Models\Group  $group
     * @return void
     */
    public function created(Group $group)
    {
        $faqs = Faq::all();

        foreach ($faqs as $faq) {
            $group->groupFaqs()->create([
                'title' => $faq->title,
                'description' => $faq->description,
                'type' => $faq->type,
            ]);
        }
    }

    /**
     * Handle the group "updated" event.
     *
     * @param  \App\Models\Group  $group
     * @return void
     */
    public function updated(Group $group)
    {
        $oldImage = $group->getOriginal('image_id');
        $oldAttritionImage = $group->getOriginal('attrition_image_id');

        if ($group->wasChanged('image_id') && !is_null($oldImage)) {
            Image::find($oldImage)->delete();
        }

        if ($group->wasChanged('attrition_image_id') && !is_null($oldAttritionImage)) {
            Image::find($oldAttritionImage)->delete();
        }
    }

    /**
     * Handle the group "forceDeleted" event.
     *
     * @param  \App\Models\Group  $group
     * @return void
     */
    public function forceDeleted(Group $group)
    {
        if ($group->image()->exists()) {
            $group->image->delete();
        }

        if ($group->attrition_image()->exists()) {
            $group->attrition_image->delete();
        }
    }
}
