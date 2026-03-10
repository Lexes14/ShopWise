<?php
declare(strict_types=1);

class ModuleController extends Controller
{
    protected string $module = 'module';
    protected string $title = 'Module';

    protected function moduleIndex(array $records = [], array $extra = []): void
    {
        $this->requireAuth();
        $this->render('shared/module', array_merge([
            'flash' => $this->getFlash(),
            'user' => $this->user(),
            'module' => $this->module,
            'title' => $this->title,
            'section' => 'index',
            'records' => $records,
            'extra' => $extra,
            'csrf' => Auth::csrfGenerate(),
        ], $extra));
    }

    protected function moduleSection(string $section, array $extra = []): void
    {
        $this->requireAuth();
        $this->render('shared/module', array_merge([
            'flash' => $this->getFlash(),
            'user' => $this->user(),
            'module' => $this->module,
            'title' => $this->title,
            'section' => $section,
            'records' => [],
            'extra' => $extra,
            'csrf' => Auth::csrfGenerate(),
        ], $extra));
    }

    protected function done(string $message, string $redirect, ?string $type = null): void
    {
        if ($type === null) {
            $lower = strtolower($message);
            $type = (str_contains($lower, 'fail') || str_contains($lower, 'error') || str_contains($lower, 'cannot') || str_contains($lower, 'not found'))
                ? 'danger'
                : 'success';
        }
        $this->flash($message, $type);
        $this->redirect($redirect);
    }
}
