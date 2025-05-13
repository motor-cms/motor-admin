<?php

namespace Motor\Admin\Http\Resources;

class LanguageResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'           => (int) $this->id,
            'iso_639_1'    => $this->iso_639_1,
            'english_name' => $this->english_name,
            'native_name'  => $this->native_name,
        ];
    }
}
