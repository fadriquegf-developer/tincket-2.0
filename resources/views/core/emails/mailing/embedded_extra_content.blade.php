<table style="text-align: left; width: 650px; font-family: Helvetica,Arial,sans-serif;" border="0" cellpadding="2"
    cellspacing="8">
    <tbody>
        @foreach($mailing->embedded_entities as $entity)
            @if(!($loop->index % 2))
                <tr>
            @endif
                @includeIf('core.emails.mailing.partials.' . strtolower(collect(explode('\\', get_class($entity)))->last()))
                @if(!(($loop->index + 1) % 2))
                    </tr>
                @endif
        @endforeach
    </tbody>
</table>