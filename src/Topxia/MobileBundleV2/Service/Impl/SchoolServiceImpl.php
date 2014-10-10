<?php


namespace Topxia\MobileBundleV2\Service\Impl;
use Topxia\MobileBundleV2\Service\BaseService;
use Topxia\MobileBundleV2\Service\SchoolService;
use Symfony\Component\HttpFoundation\Response;
use Topxia\Common\ArrayToolkit;

class SchoolServiceImpl extends BaseService implements SchoolService {

    public $banner;

    public function sendSuggestion()
    {
        $info = $this->getParam("info");
        $type = $this->getParam("type", 'bug');
        $contact = $this->getParam("contact");

        if (empty($info)) {
            return $this->createErrorResponse('error', '反馈内容不能为空！');
        }

        return $info;
    }

    public function getShradCourseUrl()
    {
        $courseId = $this->request->get("courseId");
        if (empty($courseId)) {
            return new Response("课程不存在或已删除");
        }

        return $this->controller->redirect($this->controller->generateUrl('course_show', array('id' => $courseId)));
    }

    public function getUserterms()
    {
        $setting = $this->controller->getSettingService()->get('auth', array());
        $userTerms = "暂无服务条款";
        if (array_key_exists("user_terms_body", $setting)) {
            $userTerms = $setting['user_terms_body'];
        }
        
        return $this->controller->render('TopxiaMobileBundleV2:Content:index.html.twig', array(
            'content' => $userTerms
        ));
    }

    public function getSchoolInfo()
    {
        $mobile = $this->controller->getSettingService()->get('mobile', array());
        
        return $this->controller->render('TopxiaMobileBundleV2:Content:index.html.twig', array(
            'content' => $this->controller->convertAbsoluteUrl($this->request, $mobile['about'])
        ));
    }

    public function getWeekRecommendCourses()
    {
        $mobile = $this->controller->getSettingService()->get('mobile', array());

        $courseIds = explode(",", $mobile['courseIds']);
        $courses = $this->controller->getCourseService()->findCoursesByIds($courseIds);
        $courses = ArrayToolkit::index($courses,'id');
        $sortedCourses = array();
        foreach ( $courseIds as $value){
            if(!empty($value))
                $sortedCourses[] = $courses[$value];
        }

        $result = array(
            "start"=>0,
            "limit"=>3,
            "data"=>$this->controller->filterCourses($sortedCourses));
        return $result;
    }

    public function getRecommendCourses()
    {
        return $this->getCourseByType("recommendedSeq");
    }

    public function getLatestCourses()
    {
        return $this->getCourseByType("latest");
    }

    private function getCourseByType($sort)
    {
        $start = (int) $this->getParam("start", 0);
        $limit = (int) $this->getParam("limit", 10);

        $conditions = array(
            'status' => 'published',
            'type' => 'normal',
        );
        $total  = $this->getCourseService()->searchCourseCount($conditions);
        $courses = $this->controller->getCourseService()->searchCourses($conditions, $sort, $start,  $limit);
        $result = array(
            "start"=>$start,
            "limit"=>$limit,
            "total"=>$total,
            "data"=>$this->controller->filterCourses($courses));

        return $result;
    }

    public function getSchoolAnnouncement()
    {
        $mobile = $this->getSettingService()->get('mobile', array());
        return array(
            "info"=>$mobile['notice'],
            "action"=>"none",
            "params"=>array()
            );
    }

    public function getSchoolBanner()
    {
        $banner = array();
        $mobile = $this->getSettingService()->get('mobile', array());
        $baseUrl = $this->request->getSchemeAndHttpHost();
        $keys = array_keys($mobile);
        for ($i=0; $i < count($keys); $i++) {
            $result = stripos($keys[$i], 'banner');
            if (is_numeric($result)) {
                $bannerClick = $mobile[$keys[$i]];
                $i = $i +1;
                $bannerParams = $mobile[$keys[$i]];
                $i = $i +1;
                $bannerUrl = $mobile[$keys[$i]];
                if (!empty($bannerUrl)) {   
                    $banner[] = array(
                        "url"=>$baseUrl . '/' . $bannerUrl,
                        "action"=>$bannerClick == 0 ? "none" : "webview",
                        "params"=>$bannerParams
                    );
                }
            }
        }
        return $banner;
    }

    private function getBannerFromWeb()
    {
        $blocks = $this->getBlockService()->getContentsByCodes(array('home_top_banner'));
        $baseUrl = $this->request->getSchemeAndHttpHost();

        $this->banner = array();
        if (empty($blocks)) {
            return $banner;
        }

        $content = $this;
        //replace <a><img></a>
        $blocks = preg_replace_callback('/<a href=[\'\"](.*?)[\'\"]><img src=[\'\"](.*?)[\'\"][^>]\/><\/a>/', function($matches) use ($baseUrl, $content) {
            $matcheUrl = $matches[2];
            if (stripos($matcheUrl, "../") == 0) {
                $matcheUrl = substr($matcheUrl, 3);
            }
            $url = "${baseUrl}/$matcheUrl";
            $content->banner[] = array(
                "url"=>$url,
                "action"=>"webview",
                "params"=>$matches[1]
                );
            return '';
        }, $blocks['home_top_banner']);

        //replace img
        $blocks = preg_replace_callback('/<img src=[\'\"](.*?)[\'\"]>/', function($matches) use ($baseUrl, $content) {
            $matcheUrl = $matches[1];
            if (stripos($matcheUrl, "../")) {
                $matcheUrl = substr($matcheUrl, 3);
            }
            $url = "${baseUrl}/$matcheUrl";
            $content->banner[] = array(
                "url"=>$url,
                "action"=>"none",
                "params"=>''
                );
            return '';
        }, $blocks);
        return $this->banner;
    }

    public function getSchoolSiteByQrCode()
    {
        $mobile = $this->controller->getSettingService()->get('mobile', array());
        if (empty($mobile['enabled'])) {
            return $this->createErrorResponse('client_closed', '没有搜索到该网校！');
        }

        $token = $this->controller->getUserToken($request);
        if (empty($token) or  $token['type'] != self::TOKEN_TYPE) {
            $token = null;
        }

        if (empty($token)) {
            $user = null;
        } else {
            $user = $this->controller->getUserService()->getUser($token['userId']);
        }

        $site = $this->controller->getSettingService()->get('site', array());

        $result = array(
            'token' => empty($token) ? '' : $token['token'],
            'user' => empty($user) ? null : $this->filterUser($user),
            'site' => $this->getSiteInfo($request)
        );
        
        return $result;
    }

    public function getSchoolSite() {
        $mobile = $this->controller->getSettingService()->get('mobile', array());
        if (empty($mobile['enabled'])) {
            return $this->createErrorResponse('client_closed', '没有搜索到该网校！');
        }

        $site = $this->controller->getSettingService()->get('site', array());
        $result = array(
            'site' => $this->getSiteInfo($this->controller->request)
        );
        
        return $result;
    }

    private function getSchoolAnnouncementFromDb()
    {
        $result = array();
    }

    private function getSchoolBannerFromDb()
    {
        $banner = array(
            array(
                "url"=>"",
                "action"=>"none",
                "params"=>array()
                ),
            array(
                "url"=>"",
                "action"=>"none",
                "params"=>array()
                ),
            array(
                "url"=>"",
                "action"=>"none",
                "params"=>array()
                )
        );
        return $banner;
    }
}
