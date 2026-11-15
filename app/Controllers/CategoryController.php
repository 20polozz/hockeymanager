<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Repositories\CategoryRepository;

/**
 * Categories del club (Sènior, Júnior...). L'ordre va de la més jove a la més gran:
 * a les convocatòries es poden convocar jugadors/es de la mateixa categoria o d'inferiors.
 */
final class CategoryController extends Controller
{
    public function index(): void
    {
        $this->view('pages/categories/index', [
            'pageTitle'  => 'Categories',
            'activeNav'  => 'teams',
            'categories' => CategoryRepository::all(),
            'editing'    => isset($_GET['editar']) ? CategoryRepository::find((int)$_GET['editar']) : null,
        ]);
        forget_input();
    }

    public function store(): void
    {
        [$d, $errors] = CategoryRepository::validate($_POST, null);
        if ($errors) {
            $this->fail($errors, '/equips/categories');
        }
        CategoryRepository::create($d);
        flash('success', 'Categoria «' . $d['name'] . '» creada.');
        redirect('/equips/categories');
    }

    public function update(int $id): void
    {
        $this->findOr404($id);
        [$d, $errors] = CategoryRepository::validate($_POST, $id);
        if ($errors) {
            $this->fail($errors, '/equips/categories?editar=' . $id);
        }
        CategoryRepository::update($id, $d);
        flash('success', 'Categoria actualitzada.');
        redirect('/equips/categories');
    }

    public function move(int $id): void
    {
        $this->findOr404($id);
        CategoryRepository::move($id, ($_POST['dir'] ?? '') === 'up' ? -1 : 1);
        redirect('/equips/categories');
    }

    public function destroy(int $id): void
    {
        $c = $this->findOr404($id);
        if (CategoryRepository::inUse($id)) {
            flash('error', 'La categoria «' . $c['name'] . '» té equips assignats. Canvia\'ls de categoria abans d\'eliminar-la.');
            redirect('/equips/categories');
        }
        CategoryRepository::delete($id);
        flash('success', 'Categoria «' . $c['name'] . '» eliminada.');
        redirect('/equips/categories');
    }

    /**
     * @return array<string,mixed>
     */
    private function findOr404(int $id): array
    {
        $c = CategoryRepository::find($id);
        if (!$c) {
            throw new HttpException(404);
        }
        return $c;
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
