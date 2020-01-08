<?php


namespace EduBoxBundle\Controller\Admin;


use EduBoxBundle\Entity\StudentsGroup;
use EduBoxBundle\Entity\Subject;
use EduBoxBundle\Entity\User;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MarkCRUDController extends CRUDController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction()
    {
        $this->admin->checkAccess('list');
        $request = $this->getRequest();
        if ($this->isGranted('ROLE_TEACHER')) {
            return $this->teacherJournals();
        }
        elseif ($this->isGranted('ROLE_STUDENT')) {
            $this->admin->setLabel('Diary');
            return $this->studentDiary((int)$request->get('next'));
        }
        elseif ($this->isGranted('ROLE_PARENT')) {
            $this->admin->setLabel('Diary');
            return $this->parentDiary((int)$request->get('next'), (int)$request->get('student'));
        }
        throw $this->createAccessDeniedException();
    }

    public function parentDiary($next = 0, $student = 0)
    {
        $parentManager = $this->get('edubox.parent_manager');
        $parent = $this->getUser();

        $oneStudent = $parentManager->hasOneStudent($parent);
        if ($oneStudent instanceof  User) {
            $student = $oneStudent;
        }
        else {
            $student = $this->getDoctrine()->getRepository(User::class)->find($student);
        }
        if ($student instanceof User) {
            if ($parentManager->hasStudent($parent, $student)) {
                $diary = $this->get('edubox.diary_manager')->getDiary($student, $next);
                return $this->renderWithExtraParams('EduBoxBundle:Admin:mark/parent/diary.html.twig', [
                    'diary' => $diary,
                    'next' => $next,
                    'user' => $student,
                ]);
            }
        }
        return $this->renderWithExtraParams('EduBoxBundle:Admin:mark/parent/list.html.twig', [
            'students' => $parentManager->getStudents($parent),
        ]);

    }

    public function studentDiary($next = 0)
    {
        $user = $this->getUser();
        $diary = $this->get('edubox.diary_manager')->getDiary($user, $next);
        return $this->renderWithExtraParams('EduBoxBundle:Admin:mark/diary.html.twig', [
            'diary' => $diary,
            'next' => $next,
        ]);
    }

    /**
     * Get teacher subject-students group (journal)
     */
    public function teacherJournals()
    {
        $user = $this->getUser();
        $teacher_manager = $this->get('edubox.teacher_manager');
        $subjects = $teacher_manager->getSubjects($user);
        return $this->renderWithExtraParams('EduBoxBundle:Admin:mark/journal_list.html.twig', [
            'subjects' => $subjects,
        ]);
    }

    public function editJournalAction($subjectId, $studentsGroupId, $quarter = null)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $subject = $em->getRepository(Subject::class)->find($subjectId);
        if (!$subject)
        {
            throw $this->createNotFoundException('Subject not found');
        }
        $studentsGroup = $em->getRepository(StudentsGroup::class)->find($studentsGroupId);
        if (!$studentsGroup)
        {
            throw $this->createNotFoundException('Students group not found');
        }
        $quarter_manager = $this->get('edubox.quarter_manager');
        if (!$quarter_manager->hasQuarter($quarter)) {
            $quarter = $quarter_manager->getCurrentQuarter();
        }

        $students_group_manager = $this->get('edubox.students_group_manager');
        $students = $students_group_manager->getStudents($studentsGroup);

        $mark_manager = $this->get('edubox.mark_manager');
        $marks = $mark_manager->getMarks($subject, $studentsGroup, $quarter);
        $averages = $quarter_manager->formatResult($quarter_manager->getAverages($subject, $students, $quarter));
        $dates = $mark_manager->getDatesTree($subject, $studentsGroup, $quarter);

        // Add daysCount
        $twig = $this->get('twig');
        $function = new \Twig_SimpleFunction('days_count', function (array $days) {
            return count($days, COUNT_RECURSIVE);
        });
        $twig->addFunction($function);

        return $this->renderWithExtraParams('EduBoxBundle:Admin:mark/edit.html.twig', [
            'subject' => $subject,
            'studentsGroup' => $studentsGroup,
            'students' => $students,
            'averages' => $averages,
            'quarterNumber' => $quarter,
            'marks' => $marks,
            'dates' => $dates,
        ]);
    }

    public function createMarkAction($subjectId, $studentsGroupId, Request $request) {
        $mark = $request->request->get('mark');
        $date = $request->request->get('date');
        $hour = (int)$request->request->get('hour');
        $comment = $request->request->get('comment');
        $userId = (int)$request->request->get('userId');
        $subjectId = (int)$subjectId;
        $studentsGroupId = (int)$studentsGroupId;
        if (!$userId || !$mark || !$date || !$hour || !isset($comment) || !$subjectId || !$studentsGroupId) {
            throw new \Exception('Required attributes not found', 500);
        }
        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('Student not found');
        }
        $subject = $this->getDoctrine()->getRepository(Subject::class)->find($subjectId);
        if (!$subject) {
            throw $this->createNotFoundException('Subject not found');
        }
        $studentsGroup = $this->getDoctrine()->getRepository(StudentsGroup::class)->find($studentsGroupId);
        if (!$studentsGroup) {
            throw $this->createNotFoundException('Students group not found');
        }
        $date = new \DateTime($date);
        $mark_manager = $this->get('edubox.mark_manager');
        $quarter_manager = $this->get('edubox.quarter_manager');
        $students_group_manager = $this->get('edubox.students_group_manager');
        $students = $students_group_manager->getStudents($studentsGroup);
        $quarter = $quarter_manager->getQuarterByDate($date);

        $mark_manager->createMark($subject, $user, $mark, $date, $hour, $comment);
        $averages = $quarter_manager->formatResult($quarter_manager->getAverages($subject, $students, $quarter), true);
        return new JsonResponse($averages);
    }
}