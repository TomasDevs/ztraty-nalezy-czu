<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\App;
use App\Forms\CatalogForm;
use App\Repositories\CatalogRepository;

abstract class CatalogController extends Controller
{
    abstract protected function repository(): CatalogRepository;

    abstract protected function formFor(array $input): CatalogForm;

    abstract protected function path(): string;

    abstract protected function template(): string;

    abstract protected function title(): string;

    /**
     * @return array{created: string, updated: string, deleted: string, inUse: string} inUse takes the item count
     */
    abstract protected function messages(): array;

    public function index(): void
    {
        $this->requireAdmin();

        view($this->template(), [
            'title' => $this->title(),
            'noindex' => true,
            'rows' => $this->repository()->all(),
            'path' => $this->path(),
            'form' => $this->formFor([]),
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();

        $this->save(null, $this->messages()['created']);
    }

    public function update(string $id): void
    {
        $this->requireAdmin();

        $this->save($this->findRow($id)['id'], $this->messages()['updated']);
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $row = $this->findRow($id);
        $count = $this->repository()->itemCount($row['id']);

        if ($count > 0) {
            App::session()->flash('error', sprintf($this->messages()['inUse'], $count));
        } else {
            $this->repository()->delete($row['id']);
            App::session()->flash('success', $this->messages()['deleted']);
        }

        redirect($this->path(), 303);
    }

    private function save(?int $id, string $success): never
    {
        $form = $this->formFor($_POST);
        $taken = array_column(array_filter($this->repository()->all(), fn (array $row) => $row['id'] !== $id), 'slug');

        if (!$form->validate($taken)) {
            App::session()->flash('error', reset($form->errors));
        } else {
            $id === null ? $this->repository()->create($form->toData()) : $this->repository()->update($id, $form->toData());
            App::session()->flash('success', $success);
        }

        redirect($this->path(), 303);
    }

    private function findRow(string $id): array
    {
        return $this->repository()->find((int) $id) ?? abort(404);
    }
}
