function  studentInfo(student,name){
	openwinx('/school/student/info?student='+student,'studentInfo','学生详情《'+name+'》');
}
function  teacherInfo(teacher,name){
	openwinx('/school/teacher/info?teacher='+teacher,'teacherInfo','老师详情《'+name+'》');
}