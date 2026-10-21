@extends('layouts.admin')

@section('title', 'Quiz Management')
@section('heading', 'Quiz Questions')
@section('eyebrow', 'Assessment Content')

@section('back')
    <x-back-button />
@endsection

@section('content')
    <section class="admin-panel">
        <div class="panel-header">
            <h2>{{ $editing ? 'Edit Question' : 'Create Question' }}</h2>
        </div>
        <form method="post" action="{{ $editing ? route('admin.questions.update', $editing) : route('admin.questions.store') }}" class="admin-form" data-unsaved-guard>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="form-grid">
                <div class="field-group">
                    <label for="scenario_id">Scenario</label>
                    <select id="scenario_id" name="scenario_id" required>
                        <option value="" disabled @selected(! old('scenario_id', $editing->scenario_id ?? null))>Select a scenario</option>
                        @foreach ($scenarios as $scenario)
                            <option value="{{ $scenario->id }}" @selected((int) old('scenario_id', $editing->scenario_id ?? 0) === $scenario->id)>{{ $scenario->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active" @selected(old('status', $editing->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $editing->status ?? 'active') === 'inactive')>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="field-group">
                <label for="question">Question</label>
                <textarea id="question" name="question" rows="3" required>{{ old('question', $editing->question ?? '') }}</textarea>
            </div>

            <div class="form-grid">
                <div class="field-group">
                    <label for="option_a">Option A</label>
                    <input type="text" id="option_a" name="option_a" value="{{ old('option_a', $editing->option_a ?? '') }}" required>
                </div>
                <div class="field-group">
                    <label for="option_b">Option B</label>
                    <input type="text" id="option_b" name="option_b" value="{{ old('option_b', $editing->option_b ?? '') }}" required>
                </div>
                <div class="field-group">
                    <label for="option_c">Option C</label>
                    <input type="text" id="option_c" name="option_c" value="{{ old('option_c', $editing->option_c ?? '') }}" required>
                </div>
                <div class="field-group">
                    <label for="option_d">Option D</label>
                    <input type="text" id="option_d" name="option_d" value="{{ old('option_d', $editing->option_d ?? '') }}" required>
                </div>
                <div class="field-group">
                    <label for="correct_answer">Correct Answer</label>
                    <select id="correct_answer" name="correct_answer" required>
                        @foreach (\App\Models\QuizQuestion::OPTION_KEYS as $key)
                            <option value="{{ $key }}" @selected(old('correct_answer', $editing->correct_answer ?? 'A') === $key)>{{ $key }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="field-group">
                <label for="explanation">Explanation</label>
                <textarea id="explanation" name="explanation" rows="3">{{ old('explanation', $editing->explanation ?? '') }}</textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create Question' }}</button>
                @if ($editing)
                    <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary">Cancel</a>
                @endif
            </div>
        </form>
    </section>

    <div class="section-title-row">
        <h2>Question List</h2>
        <span class="muted">Grouped by category</span>
    </div>

    @forelse ($groups as $category => $group)
        <section class="admin-panel">
            <div class="panel-header">
                <h2>{{ $category }}</h2>
                <span class="status-badge active">{{ $group->count() }} {{ \Illuminate\Support\Str::plural('question', $group->count()) }}</span>
            </div>
            <div class="table-wrap table-stack">
                <table>
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Correct</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group as $question)
                            <tr>
                                <td data-label="Question">{{ \Illuminate\Support\Str::limit($question->question, 90) }}</td>
                                <td data-label="Correct">{{ $question->correct_answer }}</td>
                                <td data-label="Status"><span class="status-badge {{ $question->status }}">{{ $question->status }}</span></td>
                                <td class="table-actions" data-label="Actions">
                                    <a href="{{ route('admin.questions.index', ['edit' => $question->id]) }}" class="btn btn-secondary small">Edit</a>
                                    <form method="post" action="{{ route('admin.questions.toggle', $question) }}" class="inline-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-secondary small">{{ $question->status === 'active' ? 'Disable' : 'Enable' }}</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.questions.destroy', $question) }}" class="inline-form" onsubmit="return confirm('Delete this question?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger small">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <section class="admin-panel">
            <p class="table-empty">No questions found.</p>
        </section>
    @endforelse
@endsection
