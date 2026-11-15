<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Gate;
use App\Core\HttpException;
use App\Repositories\ActivityLog;
use App\Repositories\ExerciseRepository;

final class ExerciseController extends Controller
{
    public function index(): void
    {
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);
        $cat = (string)($_GET['categoria'] ?? '');
        $fav = !empty($_GET['preferits']);
        $this->view('pages/exercises/index', [
            'pageTitle' => 'Exercicis',
            'activeNav' => 'exercises',
            'q'         => $q,
            'cat'       => isset(ExerciseRepository::CATEGORIES[$cat]) ? $cat : '',
            'fav'       => $fav,
            'exercises' => ExerciseRepository::search($q, $cat, $fav),
            'canManage' => Gate::allows('exercises.manage'),
        ]);
    }

    public function show(int $id): void
    {
        $ex = $this->findOr404($id);
        $this->view('pages/exercises/show', [
            'pageTitle' => $ex['name'],
            'activeNav' => 'exercises',
            'ex'        => $ex,
            'media'     => ExerciseRepository::media($ex['media_url']),
            'usage'     => ExerciseRepository::usageCount($id),
            'canManage' => Gate::allows('exercises.manage'),
        ]);
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function store(): void
    {
        [$d, $errors] = ExerciseRepository::validate($_POST);
        if ($errors) {
            $this->fail($errors, '/exercicis/nou');
        }
        $id = ExerciseRepository::create($d);
        ActivityLog::add('exercise', 'Ha creat l\'exercici «' . $d['name'] . '»');
        forget_input();
        flash('success', 'Exercici afegit a la biblioteca.');
        redirect('/exercicis/' . $id);
    }

    public function edit(int $id): void
    {
        $this->form($this->findOr404($id));
    }

    public function update(int $id): void
    {
        $this->findOr404($id);
        [$d, $errors] = ExerciseRepository::validate($_POST);
        if ($errors) {
            $this->fail($errors, '/exercicis/' . $id . '/editar');
        }
        ExerciseRepository::update($id, $d);
        forget_input();
        flash('success', 'Exercici actualitzat.');
        redirect('/exercicis/' . $id);
    }

    public function destroy(int $id): void
    {
        $ex = $this->findOr404($id);
        ExerciseRepository::delete($id);
        ActivityLog::add('exercise', 'Ha eliminat l\'exercici «' . $ex['name'] . '»');
        flash('success', 'Exercici eliminat.');
        redirect('/exercicis');
    }

    public function favorite(int $id): void
    {
        $this->findOr404($id);
        $on = ExerciseRepository::toggleFavorite($id);
        $back = (string)($_POST['back'] ?? '');
        flash('success', $on ? 'Afegit als preferits.' : 'Tret dels preferits.');
        redirect(strpos($back, '/exercicis') === 0 ? $back : '/exercicis/' . $id);
    }

    /**
     * @param array<string,mixed>|null $ex
     */
    private function form(?array $ex): void
    {
        $this->view('pages/exercises/form', [
            'pageTitle' => $ex ? 'Edita l\'exercici' : 'Nou exercici',
            'activeNav' => 'exercises',
            'ex'        => $ex,
        ]);
        forget_input();
    }

    /**
     * @return array<string,mixed>
     */
    private function findOr404(int $id): array
    {
        $ex = ExerciseRepository::find($id);
        if (!$ex) {
            throw new HttpException(404);
        }
        return $ex;
    }

    /**
     * @param array<int,string> $errors
     */
    private function fail(array $errors, string $back): void
    {
        remember_input($_POST);
        foreach ($errors as $err) {
            flash('error', $err);
        }
        redirect($back);
    }
}
