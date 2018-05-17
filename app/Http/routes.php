<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(['middleware' => ['web']], function () {
    Route::any('admin/login', 'Admin\LoginController@login');
    Route::get('admin/code', 'Admin\LoginController@code');
    Route::get('checkUserAuth', 'Admin\UsersController@checkUserAuth');

    Route::group(['middleware' => ['admin.login'],'prefix'=>'admin','namespace'=>'Admin'], function () {
        //unity服务(local使用)
        //Route::get('pub/version', 'PublishController@bookVersion');  //post
        //Route::get('pub/version/test', 'PublishController@bookVersionTest'); //post

//        Route::get('config/putfile', 'ConfigController@putFile');
//        Route::post('config/changecontent', 'ConfigController@changeContent');
//        Route::post('config/changeorder', 'ConfigController@changeOrder');
//        Route::resource('config', 'ConfigController');
//
//        Route::any('upload', 'CommonController@upload');

        //管理员登录
        Route::group(['middleware' => ['role:admin|scadmin|cooperationAdmin|educationAdmin|schoolAdmin']], function() {

            Route::get('index', 'IndexController@index');
            Route::get('info', 'IndexController@info');
            Route::get('quit', 'LoginController@quit');
            Route::any('pass', 'IndexController@pass');

            Route::group(['middleware' => ['role:admin|scadmin']], function() {
                //课件操作
                //改变目录排序
                Route::post('cate/changeorder', 'CategoryController@changeOrder');
                Route::resource('category', 'CategoryController');

                Route::post('addTopCategory', 'CategoryController@addTopCategory');

                Route::get('cate/info', 'CategoryController@info');
                Route::get('cate/setPublish', 'CategoryController@setCoursePublish');
                Route::get('cate/setPublishOpen', 'CategoryController@setCoursePublishOpen');

                Route::resource('course', 'CourseController');
                Route::get('course/show' , 'CourseController@show');
                Route::get('cou/list' , 'CourseController@courseList');
                Route::get('cou/topCourse' , 'CourseController@topCourse');
                Route::get('cou/topCourseOpen' , 'CourseController@topCourseOpen');
                Route::get('cou/updateCourseName' , 'CourseController@updateCourseName');
                Route::post('qn', 'CourseController@qnUpload');

                //发布操作
                Route::get('pub/publish', 'PublishController@index');
                Route::get('pub/publishinfo', 'PublishController@publish_info');
                Route::post('pub/course', 'PublishController@publishCourse');
                //更换版本号，显示发布的课件
                Route::post('pub/version/change', 'PublishController@changeVersion');
                //发布的版本号
                Route::get('pub/pubVersion', 'versionController@index');
                //版本号操作
                Route::post('pub/addVersion', 'versionController@add');
                Route::get('pub/delVersion', 'versionController@delete');
//    Route::get('pub/course', 'PublishController@publishCourse');  //post

//    Route::get('pub/test', 'PublishController@elementCate');
                //删除配制文件操作
                Route::get('pub/delconfigfile', 'PublishController@delConfigFile');
            });

            Route::group(['middleware' => ['role:admin']], function() {
                //后台用户管理
                Route::get('users', 'UsersController@index');
                Route::get('admins', 'UsersController@backGroundAdmin');
                //用户修改详情页面
                Route::get('users/{userId}/edit', 'UsersController@updateUser');
                //修改提交方法
                Route::post('users/edit', 'UsersController@editUser');

                //管理APP操作
                Route::get('app/index', 'AppManageController@index');
                Route::post('app/addApp', 'AppManageController@add');
                Route::get('app/updateUrl', 'AppManageController@updateUrl');
                Route::get('app/delete', 'AppManageController@delete');

                //教师用户管理（停用 20180508）
                Route::get('teacher', 'TeacherController@index');
                Route::get('teacher/create', 'TeacherController@createTc');
                Route::get('teacher/downloadTc', 'TeacherController@downloadTc');
                Route::get('teacher/show', 'TeacherController@show');
            });
        });

//        Route::get('checkUserAuth', 'UsersController@checkUserAuth');

//        //教师用户管理
//        Route::get('teacher', 'TeacherController@index');
//        Route::get('teacher/create', 'TeacherController@createTc');
//        Route::get('teacher/downloadTc', 'TeacherController@downloadTc');
//        Route::get('teacher/show', 'TeacherController@show');

        //APP 用户登录注册
//        Route::get('appUser/check', 'AppUserController@index');
//        Route::get('appUser/login', 'AppUserController@loginAppUser');
//        Route::get('appUser/register', 'AppUserController@createAppUser');
//        //Route::get('appUser/downloadUser', 'AppUserController@downloadAppUser');
        Route::get('appUser/show', 'AppUserController@show');
//        Route::get('appUser/bindPhone', 'AppUserController@weChatAndPhone');
//        Route::get('appUser/{appUserId}/editShow', 'AppUserController@editShowAppUser');
//        Route::post('appUser/edit', 'AppUserController@editAppUser');
//        Route::get('appUser/forgetPass', 'AppUserController@forgetPass');

        //测试页面
        Route::get('test', 'TeacherController@test');
        Route::get('testRedis', 'TeacherController@testRedis');
        Route::get('getInviteCode', 'SchoolController@invitationCode');

        //用户授权
        Route::get('authorization/index', 'AuthorizationController@index');
        Route::get('authorization/list', 'AuthorizationController@authList');
        Route::post('authorization/add', 'AuthorizationController@add');
        Route::get('authorization/show/', 'AuthorizationController@show');
        Route::get('authorization/bind/', 'AuthorizationController@bind');
        Route::get('authorization/test', 'AuthorizationController@invitationCode');
        Route::post('authorization/addBind', 'AuthorizationController@addBind');
        Route::post('authorization/update', 'AuthorizationController@update');

        //合作商
        Route::get('cooperation/all', 'CooperationController@allCooperationOrder');
        Route::get('cooperation/show', 'CooperationController@show');

       //合作商授权 合作商管理员
        Route::get('authcooperation/login', 'CooperationController@login');
        Route::get('authcooperation/index', 'CooperationController@index');
        Route::get('authcooperation/authinfo', 'CooperationController@cooperationInfo');
        Route::get('authcooperation/list', 'CooperationController@authList');
        Route::post('authcooperation/add', 'CooperationController@add');
        Route::get('authcooperation/show/', 'CooperationController@show');
        Route::get('authcooperation/bind/', 'CooperationController@bind');
        Route::get('authcooperation/test', 'CooperationController@invitationCode');
        Route::post('authcooperation/addBind', 'CooperationController@addBind');
        Route::post('authcooperation/update', 'CooperationController@update');

        //教育局 管理员
        Route::get('autheducation/login', 'EducationController@login');
        Route::get('autheducation/index', 'EducationController@index');
        Route::get('autheducation/authinfo', 'EducationController@educationInfo');
        Route::get('autheducation/list', 'EducationController@authList');
        Route::post('autheducation/add', 'EducationController@add');
        Route::get('autheducation/show/', 'EducationController@show');
        Route::get('autheducation/bind/', 'EducationController@bind');
        Route::get('autheducation/test', 'EducationController@invitationCode');
        Route::post('autheducation/addBind', 'EducationController@addBind');
        Route::post('autheducation/update', 'EducationController@update');

        //学校 管理员
        Route::get('authschool/login', 'SchoolAdminController@login');
        Route::get('authschool/index', 'SchoolAdminController@index');
        Route::get('authschool/authinfo', 'SchoolAdminController@schoolInfo');
        Route::get('authschool/bind', 'SchoolAdminController@bind');
        Route::get('authschool/list', 'SchoolAdminController@authList');
        Route::post('authschool/addBind', 'SchoolAdminController@addBind');

        //学校
        Route::get('school/binding', 'SchoolController@binding');
        //合作商绑定学校页面
        Route::get('school/cooperationbinding', 'SchoolController@cooperationbinding');
        Route::post('school/orderinfo', 'SchoolController@addSchoolAndManager');
        //合作商绑定学校
        Route::post('school/cooperation/orderinfo', 'SchoolController@cooperationAddSchoolAndManager');
        Route::get('authorization/school', 'SchoolController@index');
        //合作商 学校列表
        Route::get('authcooperation/school', 'SchoolController@cooperationSchool');
        //教育局 学校列表
        Route::get('autheducation/school', 'SchoolController@educationSchool');

        Route::get('school/show', 'SchoolController@show');
        //合作商 学校信息页面
        Route::get('school/cooperation/show', 'SchoolController@cooperationShow');
        //教育局 学校信息页面
        Route::get('school/educationAdmin/show', 'SchoolController@educationAdminShow');
        //合作商->教育局 学校信息页面
        Route::get('school/education/show', 'SchoolController@educationShow');

        Route::post('school/update', 'SchoolController@update');
        //合作商 修改学校信息页面
        Route::post('school/cooperationupdate', 'SchoolController@CooperationUpdate');
        // 教育局管理员修改学校信息
        Route::post('school/educationadminupdate', 'SchoolController@EducationAdminUpdate');
        //合作商 ->教育局 修改学校信息页面
        Route::post('school/educationupdate', 'SchoolController@EducationUpdate');
        Route::get('school/changeInviteCode', 'SchoolController@changeInviteCode');
        Route::post('school/search', 'SchoolController@search');

        //角色权限管理
        Route::controller('roleAndPerm', 'RolePermController');
        Route::get('RoleAndPerm/info', 'RolePermController@info');
    });


//    Route::get('teacher', 'Admin\TeacherController@index');
//    Route::get('teacher/create', 'Admin\TeacherController@createTc');
//    Route::get('teacher/downloadTc', 'Admin\TeacherController@downloadTc');



});

//unity 服务（unity不能登录，所以使用这个路由）

//unity请求处理
Route::post('home/coursePage', 'Home\IndexController@article');
//获取课件详情页面
Route::get('home/coursePage', 'Home\IndexController@index');
//教师用户反馈
Route::post('home/course/feedback ', 'Home\IndexController@userFeedback');
//用户反馈统计页面
Route::get('home/feedbacklist', 'Home\IndexController@feedbacklist');
Route::get('home/feedbackTeacherlist', 'Home\IndexController@feedbackTeacherlist');
Route::get('home/feedbackDetail', 'Home\IndexController@feedbackDetail');


//课件详情页
Route::get('home/course/old', function(){
    return view('home.coursePage.course');
});

Route::get('home/course/default', function(){
    return view('home.coursePage.default2');
});


//APP 用户登录注册
Route::post('appUser/check', 'Admin\AppUserController@checkAppUser');
Route::post('appUser/login', 'Admin\AppUserController@loginAppUser');
Route::post('appUser/register', 'Admin\AppUserController@createAppUser');
Route::post('appUser/forgetPass', 'Admin\AppUserController@forgetPass');
Route::post('appUser/bindPhone', 'Admin\AppUserController@weChatAndPhone');
Route::post('appUser/getUserInfo', 'Admin\AppUserController@getUserInfo');

//app更新
Route::get('appUpdate', 'Admin\AppManageController@getNewVersion');

Route::post('pub/version', 'Admin\PublishController@bookVersion');
Route::post('pub/version/test', 'Admin\PublishController@bookVersionTest');
//    Route::get('pub/version', 'Admin\PublishController@bookVersion');

//    Route::get('pub/version/test', 'Admin\PublishController@bookVersionTest');
//教师绑定邀请码
Route::post('bindInviteCode', 'Admin\TeacherController@bindInviteCode');

//测试陆游
Route::get('project/test', function(){echo phpinfo();});

//auth 权限管理
Route::auth();

Route::get('test/page', function(){
    return view('home.coursePage.test');
});







