@if (session('status'))
    <div class="alert success" role="status">{{ session('status') }}</div>
@endif

@if (session('error'))
    <div class="alert error" role="alert">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert error" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
