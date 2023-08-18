<?php

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JoliMarkdown\Bridge\Symfony\Validator;

use JoliMarkdown\MarkdownFixer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MarkdownValidator extends ConstraintValidator
{
    public function __construct(
        private readonly MarkdownFixer $markdownFixer,
    ) {
    }

    /**
     * @param string $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Markdown) {
            throw new UnexpectedTypeException($constraint, Markdown::class);
        }

        $fixedMarkdown = $this->markdownFixer->fix((string) $value);

        if ($value !== $fixedMarkdown) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ syntax }}', $fixedMarkdown)
                ->addViolation()
            ;
        }
    }
}
