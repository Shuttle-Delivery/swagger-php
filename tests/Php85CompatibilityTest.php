<?php declare(strict_types=1);

namespace OpenApi\Tests;

use ErrorException;
use OpenApi\Generator;

class Php85CompatibilityTest extends OpenApiTestCase
{
    public function testLegacyAnnotationsMergeWithoutRuntimeDeprecations(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'swagger-php85-');
        file_put_contents($file, <<<'SOURCE'
<?php
/**
 * @OA\Info(title="Compatibility", version="1.0")
 * @OA\Schema(schema="Item", @OA\Property(property="name", type="string"))
 * @OA\PathItem(path="/compat", @OA\Get(operationId="compatGet", @OA\Response(response=200, description="OK")))
 * @OA\PathItem(path="/compat", @OA\Post(operationId="compatPost", @OA\Response(response=201, description="Created")))
 */
class CompatibilityExample {}
SOURCE
        );
        set_error_handler(static function ($severity, $message, $file, $line): void {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            $schema = Generator::scan([$file], ['logger' => $this->getTrackingLogger()]);
            $json = json_decode($schema->toJson(), true);
            $this->assertSame('3.0.0', $json['openapi']);
            $this->assertSame('Compatibility', $json['info']['title']);
            $this->assertSame('compatGet', $json['paths']['/compat']['get']['operationId']);
            $this->assertSame('compatPost', $json['paths']['/compat']['post']['operationId']);
            $this->assertSame('string', $json['components']['schemas']['Item']['properties']['name']['type']);
        } finally {
            restore_error_handler();
            unlink($file);
        }
    }
}
