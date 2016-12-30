<div class="card">
    <div class="head">
        <h1><a href="{{ $form->url() }}">{{ $title }}</a></h1>
    </div>
    <div class="card-body">
        @if ( ! $submissions)
            <h2>{{ trans('cp.empty_responses') }}</h2>
        @else
            <table class="dossier">
                @foreach($submissions as $submission)
                    <tr>
                        @foreach($fields as $key => $field)
                        <td><a href="{{ route('form.submission.show', [$form->name(), $submission['id']]) }}">{{ array_get($submission, $field) }}</a></td>
                        @endforeach
                        <td class="minor text-right">
                            {{ ($submission['date']->diffInDays() <= 14) ? $submission['date']->diffForHumans() : $submission['date']->format($format) }}
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</div>
