<?php

namespace AsyncImportBundle\Examples;

use App\Entity\User;
use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Service\ImportHandlerInterface;
use AsyncImportBundle\Service\ValidationResult;
use Doctrine\ORM\EntityManagerInterface;

/**
 * 用户导入处理器示例
 */
class UserImportHandler implements ImportHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function supports(string $entityClass): bool
    {
        return $entityClass === User::class;
    }

    public function validate(array $row, int $lineNumber): ValidationResult
    {
        $result = ValidationResult::success();

        // 验证必填字段
        if (empty($row['email'])) {
            $result->addError('邮箱不能为空');
        } elseif (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $result->addError('邮箱格式不正确');
        }

        if (empty($row['username'])) {
            $result->addError('用户名不能为空');
        } elseif (strlen($row['username']) < 3) {
            $result->addError('用户名至少需要3个字符');
        }

        // 检查邮箱是否已存在
        if (!empty($row['email'])) {
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $row['email']]);
            
            if ($existingUser) {
                $result->addWarning('邮箱已存在，将跳过该用户');
            }
        }

        return $result;
    }

    public function import(array $row, AsyncImportTask $task): void
    {
        // 检查用户是否已存在
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $row['email']]);
        
        if ($existingUser) {
            // 可以选择更新或跳过
            return;
        }

        // 创建新用户
        $user = new User();
        $user->setEmail($row['email']);
        $user->setUsername($row['username']);
        
        // 设置其他字段
        if (!empty($row['fullname'])) {
            $user->setFullname($row['fullname']);
        }
        
        if (!empty($row['phone'])) {
            $user->setPhone($row['phone']);
        }
        
        if (!empty($row['role'])) {
            $user->setRoles([$row['role']]);
        }

        // 设置默认密码
        $user->setPassword(password_hash('default123', PASSWORD_DEFAULT));
        
        $this->entityManager->persist($user);
        
        // 注意：实际的 flush 操作由批处理器控制
    }

    public function getFieldMapping(): array
    {
        return [
            '邮箱' => 'email',
            '用户名' => 'username',
            '全名' => 'fullname',
            '电话' => 'phone',
            '角色' => 'role',
        ];
    }

    public function getBatchSize(): int
    {
        return 100; // 每批处理100条记录
    }

    public function getEntityClass(): string
    {
        return User::class;
    }

    public function preprocess(array $row): array
    {
        // 数据预处理
        foreach ($row as $key => $value) {
            // 去除首尾空格
            if (is_string($value)) {
                $row[$key] = trim($value);
            }
            
            // 转换空字符串为 null
            if ($value === '') {
                $row[$key] = null;
            }
        }
        
        // 标准化邮箱
        if (!empty($row['email'])) {
            $row['email'] = strtolower($row['email']);
        }
        
        return $row;
    }
}