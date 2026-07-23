<?php
declare(strict_types=1);

namespace EduQR\Tests\Integration\Repositories;

use EduQR\Tests\IntegrationTestCase;
use EduQR\Repositories\CourseRepository;
use EduQR\Repositories\UserRepository;

final class CourseRepositoryTest extends IntegrationTestCase
{
    private ?CourseRepository $courseRepo = null;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->courseRepo = new CourseRepository();

        // Setup a dummy instructor user to associate courses with
        $userRepo = new UserRepository();
        $this->userId = $userRepo->create('Instructor', 'inst@example.com', 'pwd');
    }

    public function test_course_crud_and_status(): void
    {
        // 1. Create Course
        $courseId = $this->courseRepo->create(
            $this->userId,
            'Web Development',
            'CS101',
            'Fall 2026',
            'Learn PHP, HTML, CSS',
            'tr',
            'Web Dev EN',
            'Learn PHP EN'
        );

        $this->assertGreaterThan(0, $courseId);

        // 2. Fetch by ID and User ID
        $course = $this->courseRepo->findByIdAndUserId($courseId, $this->userId);
        $this->assertNotNull($course);
        $this->assertEquals('Web Development', $course['title']);
        $this->assertEquals('CS101', $course['code']);

        // Check if fetched active courses has 1 item
        $activeCourses = $this->courseRepo->findByUserId($this->userId);
        $this->assertCount(1, $activeCourses);
        $this->assertEquals($courseId, (int)$activeCourses[0]['id']);

        // Check archived list is empty
        $archivedCourses = $this->courseRepo->findArchivedByUserId($this->userId);
        $this->assertCount(0, $archivedCourses);

        // 3. Update Status to 'archived'
        $this->courseRepo->updateStatus($courseId, $this->userId, 'archived');

        $activeCoursesAfter = $this->courseRepo->findByUserId($this->userId);
        $this->assertCount(0, $activeCoursesAfter);

        $archivedCoursesAfter = $this->courseRepo->findArchivedByUserId($this->userId);
        $this->assertCount(1, $archivedCoursesAfter);
        $this->assertEquals($courseId, (int)$archivedCoursesAfter[0]['id']);
    }
}
