<?php

namespace AsyncImportBundle\Tests\Service\Parser;

use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Service\Parser\JsonParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(JsonParser::class)]
final class JsonParserTest extends TestCase
{
    private JsonParser $parser;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new JsonParser();
        $this->tempDir = sys_get_temp_dir() . '/json_parser_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // 清理临时文件
        $files = glob($this->tempDir . '/*');
        if (false !== $files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->parser->supports(ImportFileType::JSON));
        $this->assertFalse($this->parser->supports(ImportFileType::CSV));
        $this->assertFalse($this->parser->supports(ImportFileType::XLSX));
    }

    public function testParse(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];

        $jsonFile = $this->createJsonFile($data);

        $rows = iterator_to_array($this->parser->parse($jsonFile));

        $this->assertCount(2, $rows);
        $this->assertSame($data[0], $rows[0]);
        $this->assertSame($data[1], $rows[1]);
    }

    public function testParseWithRootKey(): void
    {
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'John Doe'],
                ['id' => 2, 'name' => 'Jane Smith'],
            ],
            'meta' => ['total' => 2],
        ];

        $jsonFile = $this->createJsonFile($data);

        $rows = iterator_to_array($this->parser->parse($jsonFile, ['rootKey' => 'users']));

        $this->assertCount(2, $rows);
        $this->assertSame($data['users'][0], $rows[0]);
        $this->assertSame($data['users'][1], $rows[1]);
    }

    public function testParseInvalidJson(): void
    {
        $jsonFile = $this->tempDir . '/invalid.json';
        file_put_contents($jsonFile, '{invalid json}');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON');

        iterator_to_array($this->parser->parse($jsonFile));
    }

    public function testCountRows(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
            ['id' => 3, 'name' => 'Bob'],
        ];

        $jsonFile = $this->createJsonFile($data);

        $count = $this->parser->countRows($jsonFile);
        $this->assertSame(3, $count);
    }

    public function testValidateFormat(): void
    {
        $jsonFile = $this->createJsonFile([['test' => 'data']]);

        $result = $this->parser->validateFormat($jsonFile);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    private function createJsonFile(mixed $data): string
    {
        $filename = $this->tempDir . '/test_' . uniqid() . '.json';
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

        return $filename;
    }
}
