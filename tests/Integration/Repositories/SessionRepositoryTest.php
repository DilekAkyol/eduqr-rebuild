<?php
declare(strict_types=1);

namespace EduQR\Tests\Integration\Repositories;

use EduQR\Tests\IntegrationTestCase;
use EduQR\Repositories\SessionRepository;
use EduQR\Repositories\CourseRepository;
use EduQR\Repositories\UserRepository;

final class SessionRepositoryTest extends IntegrationTestCase
{
    private ?SessionRepository $sessionRepo = null;
    private int $courseId;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionRepo = new SessionRepository();

        $userRepo = new UserRepository();
        $this->userId = $userRepo->create('Instructor', 'inst@example.com', 'pwd');

        $courseRepo = new CourseRepository();
        $this->courseId = $courseRepo->create($this->userId, 'Course Title', 'CODE101');
    }

    public function test_session_crud_and_status(): void
    {
        // 1. Create Session
        $sessionId = $this->sessionRepo->create(
            $this->courseId,
            'Session Title',
            'ABCXYZ'
        );

        $this->assertGreaterThan(0, $sessionId);

        // 2. Fetch by ID
        $session = $this->sessionRepo->findById($sessionId);
        $this->assertNotNull($session);
        $this->assertEquals('Session Title', $session['title']);
        $this->assertEquals('ABCXYZ', $session['short_code']);
        $this->assertEquals('active', $session['status']);

        // 3. Fetch by Short Code
        $sessionByCode = $this->sessionRepo->findByShortCode('ABCXYZ');
        $this->assertNotNull($sessionByCode);
        $this->assertEquals($sessionId, (int)$sessionByCode['id']);

        // 4. Update Status to 'active'
        $this->sessionRepo->updateStatus($sessionId, 'active');
        $activeSession = $this->sessionRepo->findById($sessionId);
        $this->assertEquals('active', $activeSession['status']);
        $this->assertNotNull($activeSession['started_at']);

        // 5. Update Status to 'paused'
        $this->sessionRepo->updateStatus($sessionId, 'paused');
        $pausedSession = $this->sessionRepo->findById($sessionId);
        $this->assertEquals('paused', $pausedSession['status']);
        $this->assertNotNull($pausedSession['paused_at']);

        // 6. Update Status to 'closed'
        $this->sessionRepo->updateStatus($sessionId, 'closed');
        $closedSession = $this->sessionRepo->findById($sessionId);
        $this->assertEquals('closed', $closedSession['status']);
        $this->assertNotNull($closedSession['closed_at']);

        // 7. Save AI Analysis
        $this->sessionRepo->saveAiAnalysis($sessionId, 'AI Analysis Text Summary');
        $analyzedSession = $this->sessionRepo->findById($sessionId);
        $this->assertEquals('AI Analysis Text Summary', $analyzedSession['ai_analysis']);

        // 8. Set Anonymized
        $this->sessionRepo->setAnonymized($sessionId);
        $anonSession = $this->sessionRepo->findById($sessionId);
        $this->assertEquals(1, (int)$anonSession['is_anonymized']);

        // 9. Delete Session
        $this->sessionRepo->delete($sessionId);
        $this->assertNull($this->sessionRepo->findById($sessionId));
    }
}
