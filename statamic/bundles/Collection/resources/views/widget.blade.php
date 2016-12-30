<div class="card">
    <div class="head">
        <h1><a href="{{ $collection->editUrl() }}">{{ $title }}</a></h1>
        <a href="{{ $collection->createEntryUrl() }}" class="btn btn-primary">{{ $button }}</a>
    </div>
    <div class="card-body">
        <table class="dossier">
            @foreach($entries as $entry)
                <tr>
                    <td><a href="{{ $entry->editUrl() }}">{{ $entry->get('title') }}</a></td>
                    @if ($entry->orderType() === 'date')
                    <td class="minor">
                        {{ ($entry->date()->diffInDays() <= 14) ? $entry->date()->diffForHumans() : $entry->date()->format($format) }}
                    </td>
                    @endif
                </tr>
            @endforeach
        </table>
    </div>
</div>
