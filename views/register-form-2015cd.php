<?php
$salon_id = (int)$_REQUEST['salon_id'];

if ($salon_id > 0) {
    //根据id获取活动
    $salon = get_post($salon_id);
    if (!$salon || $salon->post_type != 'salon' || $salon->post_status != 'publish')
        $salon = null;
}
$can_register = false;
if ($salon) {
    $salon_terms = wp_get_post_terms($salon->ID, 'salon_type');
    if (count($salon_terms) > 0) {
        $salon_type = $salon_terms[0]->name;
        $salon_meta = get_post_meta($salon->ID);
        //检查是否开放报名
        $can_register = ($salon_meta['opening'][0] == 1);
    }
}
?>

<?php if ($can_register) : ?>
    <?php
    wp_enqueue_script('jquery-form');
    wp_enqueue_script('salon-form');
    ?>
    <div class="salon-register-container">
        <form class="training-register-form" id="training-register-form" action="" method="post">
            <section>
                <h1>培训报名</h1>
                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('salon-register'); ?>">
                <input type="hidden" name="salon_id" value="<?php echo $salon->ID; ?>">
                <input type="hidden" name="salon-register-form-action"
                       value="<?php echo isset($attrs['action']) ? $attrs['action'] : 'training-register' ?>">


            </section>

            <div class="lg-80" style="padding-top:1em">
                <div class="row form-group">
                    <label for="area">修学处<span class="red">*</span></label>
                    <select name="area" id="area" required>
                        <option selected disabled hidden value=''></option>
                        <option value="上海">上海</option>
                        <option value="厦门">厦门</option>
                        <option value="苏州">苏州</option>
                        <option value="北京">北京</option>
                        <option value="南京">南京</option>
                    </select>
                </div>

                <div class="row form-group">
                    <label for="city" class="">地区<span class="red">*</span></label>
                    <select name="city" id="city" required>

                    </select>
                </div>
                <div class="row form-group">
                    <label for="classtype" class="">班级类型<span class="red">*</span></label>
                    <select name="classtype" required>
                        <option selected disabled hidden value=''></option>
                        <option value="同喜">同喜</option>
                        <option value="同修">同修</option>
                    </select>

                </div>

                <div class="row form-group">
                    <label for="classseq" class="">班级编号<span class="red">*</span></label>

                    <select name="classseq" id="classseq" required>
                        <option selected disabled hidden value=''></option>
                        <script>
                            for(var i=1; i<=100 ; i++)
                                document.write('<option value="'+i+'">'+i+'</option>');
                        </script>
                    </select>
                </div>

                <div class="row form-group">
                    <label for="city" class="">姓名<span class="red">*</span></label>
                    <input type="text" name="name" required>
                </div>
                <div class="row form-group">
                    <label for="bodhi_name">法名</label>
                    <input type="text" class="form-control" name="bodhi_name" maxlength="3">
                </div>
                <div class="form-group row">
                    <label for="gender">性别<span class="red">*</span></label>
                    <select name="gender" class="form-control">
                        <option selected disabled hidden value=''></option>
                        <option value="男">男</option>
                        <option value="女"">女</option>
                    </select>
                </div>
                <div class="form-group row">
                    <label for="mobile">手机<span class="red">*</span></label>
                    <input type="text" class="form-control" name="mobile" required>
                </div>
                <div class="form-group row">
                    <label for="email">邮箱<span class="red">*</span></label>
                    <input type="email" class="form-control" name="email" required="">
                </div>


                <div class="form-group row">
                    <label for="fd_position">辅导义工岗位<span class="red">*</span></label>
                    <select name="fd_position" required>
                        <option selected disabled hidden value=''></option>
                        <option value="辅导员">辅导员</option>
                        <option value="实习辅导员">实习辅导员</option>
                        <option value="辅助员">辅助员</option>
                        <option value="无">无</option>
                    </select>
                </div>

                <div class="form-group row">
                    <label for="cd_position">传灯义工岗位<span class="red">*</span></label>
                    <select name="cd_position" required >
                        <option selected disabled hidden value=''></option>
                        <option value="沙龙组组长">沙龙组组长</option>
                        <option value="沙龙组副组长">沙龙组副组长</option>
                        <option value="沙龙点负责人">沙龙点负责人</option>
                        <option value="沙龙固定义工">沙龙固定义工</option>
                        <option value="沙龙护持义工">沙龙护持义工</option>
                        <option value="沙龙分享义工">沙龙分享义工</option>
                        <option value="传灯执事">传灯执事</option>
                        <option value="传灯知事">传灯知事</option>
                        <option value="传灯干事">传灯干事</option>
                        <option value="无">无</option>
                    </select>
                </div>

                <div class="form-group row" id="other-position">
                    <label for="other_position">其它义工岗位</label>
                    <input type="text" class="form-control" name="other_position">
                </div>



                <div class="form-group row">
                    <label for="lodge" class="sm-full">住宿<span class="red">*</span></label>
                    <input type="checkbox" class="autowidth lodge" name="lodge[]" id="lodge1" value="11日晚"/> <span
                        class="left" onclick="toggleCheckbox('lodge1');">11日晚</span>
                    <input type="checkbox" class="autowidth lodge" name="lodge[]" id="lodge2" value="12日晚"/> <span
                        class="left" onclick="toggleCheckbox('lodge2');">12日晚</span>
                    <input type="checkbox" class="autowidth lodge sm-clear" name="lodge[]" id="lodge3" value="13日晚"/> <span
                        class="left" onclick="toggleCheckbox('lodge3');">13日晚</span>
                    <input type="checkbox" class="autowidth" id="nolodge" name="lodge[]"  value="不住宿" /> <span
                        class="left" onclick="toggleCheckbox('nolodge');">不住宿</span>
                </div>

                <div class="form-group row">
                    <label for="food" class="sm-full">用餐<span class="red">*</span></label>
                    <input type="checkbox" class="autowidth food" name="food[]" value="12日午餐" id="food1"/> <span onclick="toggleCheckbox('food1');" class="left sm-break">12日午餐</span>
                    <input type="checkbox" class="autowidth food" name="food[]" value="12日晚餐" id="food2"/> <span onclick="toggleCheckbox('food2');" class="left sm-break">12日晚餐</span>
                    <input type="checkbox" class="autowidth food sm-clear" name="food[]" value="13日午餐" id="food3"/> <span onclick="toggleCheckbox('food3');" class="left sm-break">13日午餐</span>
                    <input type="checkbox" class="autowidth" id="nofood" name="food[]" value="不用餐" id="food4"/>
                    <span class="left sm-break" onclick="toggleCheckbox('nofood');">不用餐</span>
                </div>

                <div class="form-group row">
                    <button type="submit" name="training-register-button" value="Submit" class="btn btn-salon">提交
                    </button>
                </div>

                <input type="hidden" id="redirect_url" value="<?php echo $redirect_url; ?>">
            </div>
        </form>
    </div>

    <script>




        $('#area').bind('change', function (e) {
            $('#city').attr('value', $(this).attr('value'));
        });

        $('#nofood').bind('change',function(e){
            if ($(this).attr('checked')) {
                $('.food').attr('checked', false);
            }
        });

        $('.food').bind('change', function (e) {
            if ($(this).attr('checked')) {
                $('#nofood').attr('checked', false);
            }

        });

        $('#nolodge').bind('change',function(e){
            if ($(this).attr('checked')) {
                $('.lodge').attr('checked', false);
            }
        });

        $('.lodge').bind('change', function (e) {
            if ($(this).attr('checked')) {
                $('#nolodge').attr('checked', false);
            }

        });

        function toggleCheckbox(id){
            var elem = $('#'+id);
            var c = !!elem.attr('checked');
            elem.attr('checked',!c);
            elem.trigger("change");
        }

        var cities =  ['上海','杭州','靖江','哈尔滨','鸡西','义乌','宝鸡',
            '佛山','北京','南京','合肥','淮南','凤台','镇江','扬州',
            '苏州','常州','徐州','泰安','无锡','厦门','福安',
            '福州','广州','深圳','温州','龙岩','漳州','景德镇',
            '永安','泉州'
        ];
        $(document).ready(function(){
            $('#city').append('<option selected disabled hidden value=""></option>');
            for(var i=0;i<cities.length;i++){
                $('#city').append('<option value="'+ cities[i] +'">'+cities[i]+'</option>');
            }

        });
    </script>
<?php endif; ?>