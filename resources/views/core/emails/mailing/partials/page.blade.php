<td style="vertical-align: top;">
    <a href="{{ replace_route_parameters(config('clients.frontend.routes.'.config('app.locale').'.cms.page.show'), [
                $entity->slug,
                $entity->id
    ], config('clients.frontend.url')) }}" style="color: #000; text-decoration: none;">                            
        <img src="{{ $entity->image }}" width="320" style="width: 320px"/>                    
        <span style="font-size: 24px; display: block;">{{ $entity->name }}</span>
        <span style="margin-bottom: 25px;">{{ \Illuminate\Support\Str::limit(strip_tags(html_entity_decode($entity->content)), 200) }}</span>
    </a>
</td>