<?php

namespace Spatie\FlareClient;

use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame as SpatieFrame;
use Spatie\FlareClient\Concerns\HasContext;
use Spatie\FlareClient\Concerns\UsesTime;
use Spatie\FlareClient\Context\ContextProvider;
use Spatie\FlareClient\Contracts\ProvidesFlareContext;
use Spatie\FlareClient\Glows\Glow;
use Spatie\FlareClient\Solutions\ReportSolution;
use Spatie\LaravelIgnition\Exceptions\ViewException;
use Spatie\IgnitionContracts\Solution;
use Throwable;

class Report
{
    use UsesTime;
    use HasContext;

    protected Backtrace $stacktrace;

    protected string $exceptionClass = '';

    protected string $message = '';

    protected array $glows = [];

    protected array $solutions = [];

    protected ContextProvider $context;

    protected ?string $applicationPath = null;

    protected ?string $applicationVersion = null;

    protected array $userProvidedContext = [];

    protected array $exceptionContext = [];

    protected Throwable $throwable;

    protected string $notifierName = 'Flare Client';

    protected ?string $languageVersion = null;

    protected ?string $frameworkVersion = null;

    protected ?int $openFrameIndex = null;

    public static function createForThrowable(
        Throwable $throwable,
        ContextProvider $context,
        ?string $applicationPath = null,
        ?string $version = null
    ): self {
        return (new static())
            ->setApplicationPath($applicationPath)
            ->throwable($throwable)
            ->useContext($context)
            ->exceptionClass(self::getClassForThrowable($throwable))
            ->message($throwable->getMessage())
            ->stackTrace(Backtrace::createForThrowable($throwable)->applicationPath($applicationPath ?? ''))
            ->exceptionContext($throwable)
            ->setApplicationVersion($version);
    }

    protected static function getClassForThrowable(Throwable $throwable): string
    {
        if ($throwable instanceof ViewException) {
            if ($previous = $throwable->getPrevious()) {
                return get_class($previous);
            }
        }

        return get_class($throwable);
    }

    public static function createForMessage(
        string $message,
        string $logLevel,
        ContextProvider $context,
        ?string $applicationPath = null
    ): self {
        $stacktrace = Backtrace::create()->applicationPath($applicationPath ?? '');

        return (new static())
            ->setApplicationPath($applicationPath)
            ->message($message)
            ->useContext($context)
            ->exceptionClass($logLevel)
            ->stacktrace($stacktrace)
            ->openFrameIndex($stacktrace->firstApplicationFrameIndex());
    }

    public function exceptionClass(string $exceptionClass): self
    {
        $this->exceptionClass = $exceptionClass;

        return $this;
    }

    public function getExceptionClass(): string
    {
        return $this->exceptionClass;
    }

    public function throwable(Throwable $throwable): self
    {
        $this->throwable = $throwable;

        return $this;
    }

    public function getThrowable(): ?Throwable
    {
        return $this->throwable;
    }

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function stacktrace(Backtrace $stacktrace)
    {
        $this->stacktrace = $stacktrace;

        return $this;
    }

    public function getStacktrace(): Backtrace
    {
        return $this->stacktrace;
    }

    public function notifierName(string $notifierName): self
    {
        $this->notifierName = $notifierName;

        return $this;
    }

    public function languageVersion(string $languageVersion): self
    {
        $this->languageVersion = $languageVersion;

        return $this;
    }

    public function frameworkVersion(string $frameworkVersion): self
    {
        $this->frameworkVersion = $frameworkVersion;

        return $this;
    }

    public function useContext(ContextProvider $request): self
    {
        $this->context = $request;

        return $this;
    }

    public function openFrameIndex(?int $index): self
    {
        $this->openFrameIndex = $index;

        return $this;
    }

    public function setApplicationPath(?string $applicationPath): self
    {
        $this->applicationPath = $applicationPath;

        return $this;
    }

    public function getApplicationPath(): ?string
    {
        return $this->applicationPath;
    }

    public function setApplicationVersion(?string $applicationVersion): self
    {
        $this->applicationVersion = $applicationVersion;

        return $this;
    }

    public function getApplicationVersion(): ?string
    {
        return $this->applicationVersion;
    }

    public function view(?View $view)
    {
        $this->view = $view;

        return $this;
    }

    public function addGlow(Glow $glow)
    {
        $this->glows[] = $glow->toArray();

        return $this;
    }

    public function addSolution(Solution $solution)
    {
        $this->solutions[] = ReportSolution::fromSolution($solution)->toArray();

        return $this;
    }

    public function userProvidedContext(array $userProvidedContext)
    {
        $this->userProvidedContext = $userProvidedContext;

        return $this;
    }

    /** @deprecated  */
    public function groupByTopFrame()
    {
        $this->groupBy = GroupingTypes::TOP_FRAME;

        return $this;
    }

    /** @deprecated  */
    public function groupByException()
    {
        $this->groupBy = GroupingTypes::EXCEPTION;

        return $this;
    }

    public function allContext(): array
    {
        $context = $this->context->toArray();

        $context = array_merge_recursive_distinct($context, $this->exceptionContext);

        return array_merge_recursive_distinct($context, $this->userProvidedContext);
    }

    protected function exceptionContext(Throwable $throwable): self
    {
        if ($throwable instanceof ProvidesFlareContext) {
            $this->exceptionContext = $throwable->context();
        }

        return $this;
    }

    protected function stracktraceAsArray(): array
    {
        return array_map(
            fn (SpatieFrame $frame) => Frame::fromSpatieFrame($frame)->toArray(),
            $this->stacktrace->frames(),
        );
    }

    public function toArray(): array
    {
        return [
            'notifier' => $this->notifierName ?? 'Flare Client',
            'language' => 'PHP',
            'framework_version' => $this->frameworkVersion,
            'language_version' => $this->languageVersion ?? phpversion(),
            'exception_class' => $this->exceptionClass,
            'seen_at' => $this->getCurrentTime(),
            'message' => $this->message,
            'glows' => $this->glows,
            'solutions' => $this->solutions,
            'stacktrace' => $this->stracktraceAsArray(),
            'context' => $this->allContext(),
            'stage' => $this->stage,
            'message_level' => $this->messageLevel,
            'open_frame_index' => $this->openFrameIndex,
            'application_path' => $this->applicationPath,
            'application_version' => $this->applicationVersion,
        ];
    }
}