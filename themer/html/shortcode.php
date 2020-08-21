<?php defined( 'ABSPATH' ) || exit;?>
<!DOCTYPE html>
<html ng-app="shortcodes">
<head>
    <meta charset="UTF-8">
    <title>添加组件</title>
    <?php wp_print_styles(); ?>
</head>
<body>
<div class="wrap clearfix" ng-controller="Shortcodes as sc">
    <div class="panel-left pull-left">
        <ul class="nav nav-pills nav-stacked" role="tablist">
            <li ng-class="{active:tab==1||tab==undefinded}"><a href="javascript:;" ng-click="tab=1">按钮</a></li>
            <li ng-class="{active:tab==2}"><a href="javascript:;" ng-click="tab=2">栅格</a></li>
            <li ng-class="{active:tab==3}"><a href="javascript:;" ng-click="tab=3">图标</a></li>
            <li ng-class="{active:tab==4}"><a href="javascript:;" ng-click="tab=4">提示</a></li>
            <li ng-class="{active:tab==5}"><a href="javascript:;" ng-click="tab=5">面板</a></li>
            <li ng-class="{active:tab==6}"><a href="javascript:;" ng-click="tab=6">视频</a></li>
            <li ng-class="{active:tab==7}"><a href="javascript:;" ng-click="tab=7">选项卡</a></li>
            <li ng-class="{active:tab==8}"><a href="javascript:;" ng-click="tab=8">手风琴</a></li>
            <li ng-class="{active:tab==9}"><a href="javascript:;" ng-click="tab=9">地图</a></li>
        </ul>
    </div>
    <div class="panel-right pull-right form-horizontal tab-content">
        <div class="tab-pane" ng-class="{active:tab==1||tab==undefinded}">
            <div class="form-group">
                <div class="col-xs-2 control-label">风格</div>
                <div class="col-xs-10">
                    <select ng-model="btn.type" class="form-control">
                        <option value="">--请选择--</option>
                        <option ng-repeat="btn in sc.btns" value="{{btn.name}}">{{btn.value}}</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">文字</div>
                <div class="col-xs-10">
                    <input type="text" class="form-control" ng-model="btn.text" placeholder="按钮文字">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">链接</div>
                <div class="col-xs-10">
                    <input type="text" class="form-control" ng-model="btn.url" placeholder="http://">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">效果</div>
                <div class="col-xs-10 desc-prev"><p>只展示基本效果，实际效果请预览文章查看</p></div>
                <div class="col-xs-10 col-xs-offset-2">
                    <button type="button" ng-show="!btn.type||btn.type==b.name" ng-repeat="b in sc.btns" class="btn btn-{{b.name}}">{{btn.text||b.value}}</button>
                </div>
            </div>
            <div class="insert">
                <div class="insert-inner clearfix">
                    <a href="javascript:;" class="btn btn-sm btn-primary btn-insert" ng-click="sc.insert({type:'btn'})">插入文中</a>
                </div>
            </div>
        </div>
        <div class="tab-pane" ng-class="{active:tab==2}">
            <div class="gird-group" ng-repeat="gird in girds">
                <div class="form-group">
                    <div class="col-xs-2 control-label">栅格</div>
                    <div class="col-xs-10">
                        <select ng-model="gird.cols" class="form-control">
                            <option value="">--请选择--</option>
                            <option ng-repeat="col in [] | range:12" value="{{col+1}}" ng-selected="gird.cols === col+1">栅格宽度：{{col+1}}/12</option>
                        </select>
                        <a ng-if="$index!=0" ng-click="sc.removeGird($index)" class="gird-remove" href="javascript:;">×</a>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-2 control-label">内容</div>
                    <div class="col-xs-10">
                        <textarea froala ng-model="gird.body" class="form-control j-editor"></textarea>
                    </div>
                </div>
            </div>
            <a class="add-gird" href="javascript:;" ng-click="addGird();">+添加</a>
            <div class="form-group">
                <div class="col-xs-2 control-label">效果</div>
                <div class="col-xs-10 desc-prev"><p>只展示基本效果，实际效果请预览文章查看</p></div>
                <div class="col-xs-10 col-xs-offset-2">
                    <div ng-repeat="gird in girds"class="col-xs-{{gird.cols}}" ng-bind-html="toHtml(gird.body)"></div>
                </div>
            </div>
            <div class="insert">
                <div class="insert-inner clearfix">
                    <a href="javascript:;" class="btn btn-sm btn-primary btn-insert" ng-click="sc.insert({type:'gird'})">插入文中</a>
                </div>
            </div>
        </div>
        <div class="tab-pane" ng-class="{active:tab==3}">
            <div class="form-group">
                <div class="col-xs-2 control-label">图标</div>
                <div class="col-xs-10">
                    <input ng-model="icon.name" class="form-control" name="name" placeholder="图标名称，例如：home">
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>图标请参考这里：<a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">https://fontawesome.com/v4.7.0/icons/</a></p></div>

            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">效果</div>
                <div class="col-xs-10 desc-prev"><p>只展示基本效果，实际效果请预览文章查看</p></div>
                <div class="col-xs-10 col-xs-offset-2 col-text">
                    <div ng-if="icon.name"><i class="fa fa-2x fa-{{icon.name}}"></i>  fa-{{icon.name}}</div>
                </div>
            </div>
            <div class="insert">
                <div class="insert-inner clearfix">
                    <a href="javascript:;" class="btn btn-sm btn-primary btn-insert" ng-click="sc.insert({type:'icon'})">插入文中</a>
                </div>
            </div>
        </div>
        <div class="tab-pane" ng-class="{active:tab==4}">
            <div class="form-group">
                <div class="col-xs-2 control-label">风格</div>
                <div class="col-xs-10">
                    <select ng-model="alert.type" class="form-control">
                        <option value="">--请选择--</option>
                        <option ng-repeat="s in sc.btns" ng-if="s.name!='default'&&s.name!='primary'" value="{{s.name}}">{{s.value}}</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">标题图标</div>
                <div class="col-xs-10">
                    <input type="text" class="form-control" ng-model="alert.icon" name="icon" placeholder="可选，例如：home">
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>图标请参考这里：<a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">https://fontawesome.com/v4.7.0/icons/</a></p></div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">图标大小</div>
                <div class="col-xs-10">
                    <label class="radio-inline">
                        <input type="radio" ng-model="alert.size" name="size" value="0"> 小图标
                    </label>
                    <label class="radio-inline">
                        <input type="radio" ng-model="alert.size" name="size" value="1"> 大图标
                    </label>
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>标题图标大小，此页面演示效果一律显示小图标</p></div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">文本</div>
                <div class="col-xs-10">
                    <input type="text" ng-model="alert.body" class="form-control" placeholder="提示文本">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">效果</div>
                <div class="col-xs-10 desc-prev"><p>只展示基本效果，实际效果请预览文章查看</p></div>
                <div class="col-xs-10 col-xs-offset-2 col-text">
                    <div class="alert alert-{{alert.type}}" role="alert" ng-bind-html="toAlertHtml(alert.body)"></div>
                </div>
            </div>
            <div class="insert">
                <div class="insert-inner clearfix">
                    <a href="javascript:;" class="btn btn-sm btn-primary btn-insert" ng-click="sc.insert({type:'alert'})">插入文中</a>
                </div>
            </div>
        </div>
        <div class="tab-pane" ng-class="{active:tab==5}">
            <div class="form-group">
                <div class="col-xs-2 control-label">风格</div>
                <div class="col-xs-10">
                    <select ng-model="panel.type" class="form-control">
                        <option value="">--请选择--</option>
                        <option ng-repeat="s in sc.btns" value="{{s.name}}">{{s.value}}</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">标题</div>
                <div class="col-xs-10">
                    <input type="text" ng-model="panel.title" class="form-control" name="title">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">标题图标</div>
                <div class="col-xs-10">
                    <input type="text" class="form-control" ng-model="panel.icon" name="icon" placeholder="可选，例如：home">
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>图标请参考这里：<a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">https://fontawesome.com/v4.7.0/icons/</a></p></div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">内容</div>
                <div class="col-xs-10">
                    <textarea froala ng-model="panel.body" class="form-control j-editor"></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">效果</div>
                <div class="col-xs-10 desc-prev"><p>只展示基本效果，实际效果请预览文章查看</p></div>
                <div class="col-xs-10 col-xs-offset-2 col-text">
                    <div class="panel panel-{{panel.type||'default'}}"><div class="panel-heading"><i ng-if="panel.icon" class="fa fa-{{panel.icon}}"></i> {{panel.title}}</div><div class="panel-body" ng-bind-html="toHtml(panel.body)"></div></div>
                </div>
            </div>
            <div class="insert">
                <div class="insert-inner clearfix">
                    <a href="javascript:;" class="btn btn-sm btn-primary btn-insert" ng-click="sc.insert({type:'panel'})">插入文中</a>
                </div>
            </div>
        </div>
        <div class="tab-pane" ng-class="{active:tab==6}">
            <div class="form-group">
                <div class="col-xs-2 control-label">视频代码</div>
                <div class="col-xs-10">
                    <textarea ng-model="video.code" class="form-control" ng-change="videoChange()"></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">宽度</div>
                <div class="col-xs-10">
                    <input type="number" ng-model="video.width" class="form-control" name="width" ng-change="videoChange()">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">高度</div>
                <div class="col-xs-10">
                    <input type="number" ng-model="video.height" class="form-control" name="height" ng-change="videoChange()">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">居中</div>
                <div class="col-xs-10 checkbox">
                    <input type="checkbox" style="margin: 4px 0 0;" ng-model="video.center" name="center" ng-change="videoChange()">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">效果</div>
                <div class="col-xs-10 desc-prev"><p>只展示基本效果，实际效果请预览文章查看</p></div>
                <div class="col-xs-10 col-xs-offset-2 col-text" ng-bind-html="codeVideo"></div>
            </div>
            <div class="insert">
                <div class="insert-inner clearfix">
                    <a href="javascript:;" class="btn btn-sm btn-primary btn-insert" ng-click="sc.insert({type:'video'})">插入文中</a>
                </div>
            </div>
        </div>
        <div class="tab-pane" ng-class="{active:tab==7}">
            <div class="form-group">
                <div class="col-xs-2 control-label">布局</div>
                <div class="col-xs-10">
                    <select ng-model="tabs.type" class="form-control">
                        <option value="">--请选择--</option>
                        <option value="0" ng-selected="tabs.type!=1">上下布局</option>
                        <option value="1" ng-selected="tabs.type==1">左右布局</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">标题</div>
                <div class="col-xs-10">
                    <input type="text" class="form-control" ng-model="tabs.title" ng-list="||" ng-trim="false" name="title" placeholder="请输入标题">
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>使用“||”分隔添加多个选项卡</p></div>
            </div>
            <div class="form-group" ng-repeat="i in [] | range:20" ng-show="i<tabs.title.length">
                <div class="col-xs-2 control-label">内容{{i+1}}</div>
                <div class="col-xs-10">
                    <textarea froala ng-model="tabs.body[i]" class="form-control j-editor"></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">效果</div>
                <div class="col-xs-10 desc-prev"><p>只展示基本效果，实际效果请预览文章查看</p></div>
                <div class="col-xs-10 col-xs-offset-2 col-text">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li ng-class="{active:$first}" ng-repeat="tab1 in tabs.title"><a ng-href="#{{tab1}}" role="tab" data-toggle="tab">{{tab1}}</a></li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content">
                        <div ng-class="{active:$first}" class="tab-pane" ng-repeat="tab2 in tabs.title" id="{{tab2}}" ng-bind-html="toHtml(tabs.body[$index])">
                        </div>
                    </div>
                </div>
            </div>
            <div class="insert">
                <div class="insert-inner clearfix">
                    <a href="javascript:;" class="btn btn-sm btn-primary btn-insert" ng-click="sc.insert({type:'tabs'})">插入文中</a>
                </div>
            </div>
        </div>
        <div class="tab-pane" ng-class="{active:tab==8}">
            <div class="form-group">
                <div class="col-xs-2 control-label">标题</div>
                <div class="col-xs-10">
                    <textarea ng-model="accordion.title" ng-list="&#10;" ng-trim="false" class="form-control"></textarea>
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>使用回车键换行添加多个选项卡</p></div>
            </div>
            <div class="form-group" ng-repeat="x in [] | range:20" ng-show="x<accordion.title.length">
                <div class="col-xs-2 control-label">内容{{x+1}}</div>
                <div class="col-xs-10">
                    <textarea froala ng-model="accordion.body[x]" class="form-control j-editor"></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">效果</div>
                <div class="col-xs-10 desc-prev"><p>只展示基本效果，实际效果请预览文章查看</p></div>
                <div class="col-xs-10 col-xs-offset-2 col-text">
                    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                        <div class="panel panel-default" ng-repeat="title in accordion.title">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse{{$index}}" aria-expanded="true" aria-controls="collapseOne">{{title}}</a>
                                </h4>
                            </div>
                            <div id="collapse{{$index}}" class="panel-collapse collapse" ng-class="{in:$first}" role="tabpanel">
                                <div class="panel-body" ng-bind-html="toHtml(accordion.body[$index])"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="insert">
                <div class="insert-inner clearfix">
                    <a href="javascript:;" class="btn btn-sm btn-primary btn-insert" ng-click="sc.insert({type:'accordion'})">插入文中</a>
                </div>
            </div>
        </div>
        <div class="tab-pane" ng-class="{active:tab==9}">
            <div class="form-group">
                <div class="col-xs-2 control-label">位置</div>
                <div class="col-xs-10">
                    <input type="text" class="form-control" ng-model="map.pos" name="pos" placeholder="请输入位置坐标">
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>位置坐标信息，可以访问这里拾取：<a target="_blank" href="http://api.map.baidu.com/lbsapi/getpoint/index.html">http://api.map.baidu.com/lbsapi/getpoint/index.html</a></p></div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">标题</div>
                <div class="col-xs-10">
                    <input type="text" class="form-control" ng-model="map.title" placeholder="请输入标题">
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>例如公司名称</p></div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">地址</div>
                <div class="col-xs-10">
                    <input type="text" class="form-control" ng-model="map.address" placeholder="请输入地址">
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>可以是公司地址，也可以是一段介绍文字</p></div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">高度</div>
                <div class="col-xs-10">
                    <input type="text" class="form-control" ng-model="map.height" placeholder="请输入组件高度">
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>组件高度，单位是px</p></div>
            </div>
            <div class="form-group">
                <div class="col-xs-2 control-label">滚轮缩放</div>
                <div class="col-xs-10 checkbox">
                    <label><input type="checkbox" ng-model="map.scroll" value="1"> 启用</label>
                </div>
                <div class="col-xs-10 col-xs-offset-2 desc"><p>是否允许鼠标滚轮缩放，开启将可以使用鼠标滚轮放大缩小地图</p></div>
            </div>
            <div class="insert">
                <div class="insert-inner clearfix">
                    <a href="javascript:;" class="btn btn-sm btn-primary btn-insert" ng-click="sc.insert({type:'map'})">插入文中</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
if($id && $code){
    $data = get_post_meta($pid, $id, true);
    if(!$data){
        $post = wpcom::get_post($id, 'sc_'.$code);
        $data = $post->post_content;
    }
    $data = maybe_unserialize($data);
    ?>
    <script>
        var scode = {
            id: '<?php echo $id;?>',
            post: '<?php echo $pid;?>',
            type: '<?php echo $code;?>',
            data: <?php echo json_encode($data);?>
        }
    </script>
<?php }else if($pid){ ?>
    <script>
        var scode = {
            post: '<?php echo $pid;?>'
        }
    </script>
<?php } ?>
<?php do_action( 'admin_footer', '' ); do_action( 'admin_print_footer_scripts' ); ?>
</body>
</html>