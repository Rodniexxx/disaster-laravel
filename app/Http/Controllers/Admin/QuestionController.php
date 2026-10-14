<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizQuestion;
use App\Models\Scenario;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request)
    {
        $questions = QuizQuestion::query()
            ->with('scenario')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (QuizQuestion $question) => $question->scenario?->title ?? 'Unassigned')
            ->groupBy(fn (QuizQuestion $question) => $question->scenario?->title ?? 'Unassigned');

        return view('admin.questions.index', [
            'groups' => $questions,
            'scenarios' => Scenario::query()->orderBy('title')->get(),
            'editing' => $request->filled('edit')
                ? QuizQuestion::query()->find((int) $request->query('edit'))
                : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateQuestion($request);
        $data['scenario_id'] = (int) $data['scenario_id'];

        $question = QuizQuestion::create($data);

        $this->audit->log($request->user(), 'created quiz question', 'quiz_questions', ['id' => $question->id]);

        return redirect()->route('admin.questions.index')->with('status', 'Question created.');
    }

    public function update(Request $request, QuizQuestion $question)
    {
        $data = $this->validateQuestion($request);
        $data['scenario_id'] = (int) $data['scenario_id'];

        $question->update($data);

        $this->audit->log($request->user(), 'updated quiz question', 'quiz_questions', ['id' => $question->id]);

        return redirect()->route('admin.questions.index')->with('status', 'Question updated.');
    }

    public function toggle(Request $request, QuizQuestion $question)
    {
        $question->update([
            'status' => $question->status === 'active' ? 'inactive' : 'active',
        ]);

        $this->audit->log($request->user(), 'updated quiz status', 'quiz_questions', [
            'id' => $question->id, 'status' => $question->status,
        ]);

        return back()->with('status', 'Question status updated.');
    }

    public function destroy(Request $request, QuizQuestion $question)
    {
        $id = $question->id;
        $question->delete();

        $this->audit->log($request->user(), 'deleted quiz question', 'quiz_questions', ['id' => $id]);

        return redirect()->route('admin.questions.index')->with('status', 'Question deleted.');
    }

    /** @return array<string, mixed> */
    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'scenario_id' => ['required', 'integer', 'exists:scenarios,id'],
            'question' => ['required', 'string'],
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['required', 'string'],
            'option_d' => ['required', 'string'],
            'correct_answer' => ['required', Rule::in(QuizQuestion::OPTION_KEYS)],
            'explanation' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
