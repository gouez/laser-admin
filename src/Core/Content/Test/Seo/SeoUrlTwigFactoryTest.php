<?php declare(strict_types=1);

namespace Laser\Core\Content\Test\Seo;

use PHPUnit\Framework\TestCase;
use Laser\Core\Content\Seo\SeoUrlGenerator;
use Laser\Core\Content\Test\Seo\Twig\LastLetterBigTwigFilter;
use Laser\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Twig\Environment;

/**
 * @internal
 */
class SeoUrlTwigFactoryTest extends TestCase
{
    use KernelTestBehaviour;

    private Environment $environment;

    protected function setUp(): void
    {
        $this->environment = $this->getContainer()->get('laser.seo_url.twig');
    }

    public function testLoadAdditionalExtension(): void
    {
        //extension loaded via custom tag in src/Core/Framework/DependencyInjection/seo_test.xml
        static::assertIsObject($this->environment->getExtension(LastLetterBigTwigFilter::class));

        $template = '{% autoescape \''
            . SeoUrlGenerator::ESCAPE_SLUGIFY
            . '\' %}{{ product.name|lastBigLetter }}{% endautoescape %}';

        $twig = $this->environment->createTemplate($template);
        $rendered = $twig->render(['product' => ['name' => 'hello world']]);

        static::assertSame('hello-worlD', $rendered);
    }
}
