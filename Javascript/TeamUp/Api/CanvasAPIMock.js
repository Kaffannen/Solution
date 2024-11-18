//export default class CanvasAPIMock {
class CanvasAPIMock {
#rolle;
constructor(rolle){
    if (rolle === "student"){
        this.#rolle = "StudentEnrollment"
    }
    else if (rolle === "underviser"){
        this.#rolle = "TeacherEnrollment"
    }
    else{
        throw new Error("Ugyldig rolle, velg student eller underviser")
    }
}

getUserInfo(){
    return new Promise((resolve, reject) => {
    const user = {
                                        //GET https://hvl.instructure.com/api/v1/users/self
                                         "id": 81736,
                                         "name": "Christian Hagen",
                                         "created_at": "2022-07-30T08:15:49+02:00",
                                         "sortable_name": "Hagen, Christian",
                                         "short_name": "Christian Hagen",
                                         "avatar_url": "https://hvl.instructure.com/images/messages/avatar-50.png",
                                         "last_name": "Hagen",
                                         "first_name": "Christian",
                                         "locale": "nb",
                                         "effective_locale": "nb",
                                         "permissions": {
                                             "can_update_name": false,
                                             "can_update_avatar": true,
                                             "limit_parent_app_web_access": false
                                         }
                                     }
     resolve(user)
    })
}

getCourseInfo(){
    return new Promise((resolve, reject) => {
        const course = { //GET https://hvl.instructure.com/api/v1/courses/29406
                                                "id": 29406,
                                                "name": "ING303-1 24H Systemtenking og innovasjon for ingeniører",
                                                "account_id": 15739,
                                                "uuid": "8wan4Q35WCFevkPd2bsA0xVNHXhP8HI5S6w4NSZC",
                                                "start_at": null,
                                                "grading_standard_id": null,
                                                "is_public": false,
                                                "created_at": "2024-05-01T06:20:36Z",
                                                "course_code": "ING303-1 24H",
                                                "default_view": "modules",
                                                "root_account_id": 1,
                                                "enrollment_term_id": 330,
                                                "license": "private",
                                                "grade_passback_setting": null,
                                                "end_at": null,
                                                "public_syllabus": false,
                                                "public_syllabus_to_auth": false,
                                                "storage_quota_mb": 524,
                                                "is_public_to_auth_users": false,
                                                "homeroom_course": false,
                                                "course_color": null,
                                                "friendly_name": null,
                                                "apply_assignment_group_weights": false,
                                                "calendar": {
                                                    "ics": "https://hvl.instructure.com/feeds/calendars/course_8wan4Q35WCFevkPd2bsA0xVNHXhP8HI5S6w4NSZC.ics"
                                                },
                                                "time_zone": "Europe/Copenhagen",
                                                "blueprint": false,
                                                "template": false,
                                                "enrollments": [
                                                    {
                                                        "type": "student",
                                                        "role": this.#rolle,
                                                        "role_id": 3,
                                                        "user_id": 81736,
                                                        "enrollment_state": "active",
                                                        "limit_privileges_to_course_section": false
                                                    }
                                                ],
                                                "hide_final_grades": false,
                                                "workflow_state": "available",
                                                "restrict_enrollments_to_course_dates": false
                                            }
        resolve(course)
    })
}
getAssignmentInfo(){
    return new Promise((resolve, reject) => {
                            const assignment = { //https://hvl.instructure.com/api/v1/courses/29406/assignments/80710
                                                   "id": 80710,
                                                   "description": "\u003cp\u003eAlle gruppene skal levera utfylt og signert samarbeidsavtale seinast 12. september. Bruk \u003ca class=\"instructure_file_link instructure_scribd_file inline_disabled\" title=\"Lenke\" href=\"https://hvl.instructure.com/courses/29406/files/2895778?wrap=1\" target=\"_blank\" data-canvas-previewable=\"true\" data-api-endpoint=\"https://hvl.instructure.com/api/v1/courses/29406/files/2895778\" data-api-returntype=\"File\"\u003emal for samarbeidsavtale\u003c/a\u003e\u0026nbsp;som utgangspunkt for å bli einige om innhaldet i avtalen internt i gruppa. Å levera signert samarbeidsavtale er eit arbeidskrav.\u003c/p\u003e",
                                                   "due_at": "2024-09-12T21:30:00Z",
                                                   "unlock_at": "2024-08-19T22:00:00Z",
                                                   "lock_at": null,
                                                   "points_possible": 0.0,
                                                   "grading_type": "pass_fail",
                                                   "assignment_group_id": 32409,
                                                   "grading_standard_id": null,
                                                   "created_at": "2024-08-12T13:23:12Z",
                                                   "updated_at": "2024-10-01T06:31:23Z",
                                                   "peer_reviews": false,
                                                   "automatic_peer_reviews": false,
                                                   "position": 1,
                                                   "grade_group_students_individually": false,
                                                   "anonymous_peer_reviews": false,
                                                   "group_category_id": 17240,
                                                   "post_to_sis": false,
                                                   "moderated_grading": false,
                                                   "omit_from_final_grade": false,
                                                   "intra_group_peer_reviews": false,
                                                   "anonymous_instructor_annotations": false,
                                                   "anonymous_grading": false,
                                                   "graders_anonymous_to_graders": false,
                                                   "grader_count": 0,
                                                   "grader_comments_visible_to_graders": true,
                                                   "final_grader_id": null,
                                                   "grader_names_visible_to_final_grader": true,
                                                   "allowed_attempts": -1,
                                                   "annotatable_attachment_id": null,
                                                   "hide_in_gradebook": false,
                                                   "secure_params": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsdGlfYXNzaWdubWVudF9pZCI6ImY3YzljMGQyLTY5MGQtNDM4OS04OWZiLTM4NzYxZjk1YjNiZSIsImx0aV9hc3NpZ25tZW50X2Rlc2NyaXB0aW9uIjoiXHUwMDNjcFx1MDAzZUFsbGUgZ3J1cHBlbmUgc2thbCBsZXZlcmEgdXRmeWx0IG9nIHNpZ25lcnQgc2FtYXJiZWlkc2F2dGFsZSBzZWluYXN0IDEyLiBzZXB0ZW1iZXIuIEJydWsgXHUwMDNjYSBjbGFzcz1cImluc3RydWN0dXJlX2ZpbGVfbGluayBpbnN0cnVjdHVyZV9zY3JpYmRfZmlsZSBpbmxpbmVfZGlzYWJsZWRcIiB0aXRsZT1cIkxlbmtlXCIgaHJlZj1cIi9jb3Vyc2VzLzI5NDA2L2ZpbGVzLzI4OTU3Nzg_d3JhcD0xXCIgdGFyZ2V0PVwiX2JsYW5rXCIgZGF0YS1jYW52YXMtcHJldmlld2FibGU9XCJ0cnVlXCJcdTAwM2VtYWwgZm9yIHNhbWFyYmVpZHNhdnRhbGVcdTAwM2MvYVx1MDAzZVx1MDAyNm5ic3A7c29tIHV0Z2FuZ3NwdW5rdCBmb3Igw6UgYmxpIGVpbmlnZSBvbSBpbm5oYWxkZXQgaSBhdnRhbGVuIGludGVybnQgaSBncnVwcGEuIMOFIGxldmVyYSBzaWduZXJ0IHNhbWFyYmVpZHNhdnRhbGUgZXIgZWl0IGFyYmVpZHNrcmF2Llx1MDAzYy9wXHUwMDNlIn0.NUmLE_CIFLmYqO89dnDgp1HPJRJNhsSCOqmUbaoFvSo",
                                                   "lti_context_id": "f7c9c0d2-690d-4389-89fb-38761f95b3be",
                                                   "course_id": 29406,
                                                   "name": "Samarbeidsavtale",
                                                   "submission_types": [
                                                       "online_upload"
                                                   ],
                                                   "has_submitted_submissions": true,
                                                   "due_date_required": false,
                                                   "max_name_length": 255,
                                                   "in_closed_grading_period": false,
                                                   "graded_submissions_exist": true,
                                                   "is_quiz_assignment": false,
                                                   "can_duplicate": true,
                                                   "original_course_id": null,
                                                   "original_assignment_id": null,
                                                   "original_lti_resource_link_id": null,
                                                   "original_assignment_name": null,
                                                   "original_quiz_id": null,
                                                   "workflow_state": "published",
                                                   "important_dates": false,
                                                   "muted": true,
                                                   "html_url": "https://hvl.instructure.com/courses/29406/assignments/80710",
                                                   "published": true,
                                                   "only_visible_to_overrides": false,
                                                   "visible_to_everyone": true,
                                                   "locked_for_user": false,
                                                   "submissions_download_url": "https://hvl.instructure.com/courses/29406/assignments/80710/submissions?zip=1",
                                                   "post_manually": false,
                                                   "anonymize_students": false,
                                                   "require_lockdown_browser": false,
                                                   "restrict_quantitative_data": false
                                               }
        resolve(assignment)
})
}
getGroupMembers(){
    return new Promise((resolve, reject) => {
                            //https://hvl.instructure.com/api/v1/courses/29406/assignments/80710/users/81736/group_members
                            const group = [
                                            {
                                              "id": "15686",
                                              "name": "Ole Kristoffer Høivaag Jensen"
                                            },
                                            {
                                              "id": "66800",
                                              "name": "Jonas Grundvåg Rong"
                                            },
                                            {
                                              "id": "69850",
                                              "name": "Simen Hærnes Ihlen"
                                            },
                                            {
                                              "id": "80861",
                                              "name": "Martin Sollesnes Kummeneje"
                                            },
                                            {
                                              "id": "81736",
                                              "name": "Christian Hagen"
                                            },
                                            {
                                              "id": "82310",
                                              "name": "Vlad Nicusor Lipovanu"
                                            }
                                          ]
        resolve(group)
})
}
getAssignmentGroup(){
    return new Promise((resolve, reject) => {
    //https://hvl.instructure.com/api/v1/courses/29406/assignment_groups/32409

        const group = {
                        "id": 32409,
                        "name": "Oppgåver",
                        "position": 2,
                        "group_weight": 0,
                        "sis_source_id": null,
                        "integration_data": {

                        },
                        "rules": {

                        }
                      }
        resolve(group)
    })
}
}