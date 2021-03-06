<?php

namespace DigitalCreative\MegaFilter;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Controllers\ResourceIndexController;
use Laravel\Nova\Http\Requests\NovaRequest;

trait HasMegaFilterTrait
{
    /**
     * Get the actions for the given request.
     *
     * @param NovaRequest $request
     *
     * @return Collection
     */
    public function resolveActions(NovaRequest $request)
    {

        if ($request->method() === 'POST') {

            return parent::resolveActions($request)->merge($this->getMegaFilterCard($request)->actions());

        }

        return parent::resolveActions($request);

    }

    /**
     * Get the filters for the given request.
     *
     * @param NovaRequest $request
     *
     * @return Collection
     */
    public function resolveFilters(NovaRequest $request)
    {
        return parent::resolveFilters($request)->merge($this->getMegaFilterCard($request)->filters());
    }

    public function availableFields(NovaRequest $request)
    {

        $controller = $request->route()->controller;
        $fields = parent::availableFields($request);

        if (!($controller instanceof ResourceIndexController)) {

            return $fields;

        }

        $fieldsToShow = $this->getFilterState($request);

        return $fields->filter(function ($field) use ($fieldsToShow) {

            if ($field instanceof Field) {

                /**
                 * Keep computed fields untouched
                 */
                if ($field->computed()) {

                    return true;

                }

                return $fieldsToShow->contains($field->attribute);

            }

            return true;

        });
    }

    private function getMegaFilterCard(NovaRequest $request): MegaFilter
    {
        return collect($this->cards($request))->whereInstanceOf(MegaFilter::class)->first();
    }

    private function getFilterState(NovaRequest $request)
    {

        $card = $this->getMegaFilterCard($request);
        $query = collect(json_decode(base64_decode($request->query('megaFilter')), true));

        $attributes = $card->columns()->filter(function (Column $column) use ($query) {

            if ($column->permanent) {

                return true;

            }

            if ((is_bool($value = $query->get($column->attribute)))) {

                return $value;

            }

            return $column->checked;

        });

        return $attributes->pluck('attribute')->values();

    }
}
