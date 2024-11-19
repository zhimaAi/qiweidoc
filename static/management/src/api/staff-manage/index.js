/*
 * @Description: 
 * @Author: chris
 * @Date: 2022-03-16 11:54:31
 * @LastEditTime: 2023-07-10 16:33:52
 * @LastEditors: Mango
 */
import request from "../request";
const api = {};

//获取分组列表
api.user_get_corp_group_list = (data = {}) => {
  return request.post("/user/get-corp-group-list", data);
};
//修改分组名称
api.user_edit_corp_group = (data = {}) => {
  return request.post("/user/edit-corp-group", data);
};
//添加分组
api.user_add_corp_group = (data = {}) => {
  return request.post("/user/add-corp-group", data);
};
//删除分组
api.user_del_corp_group = (data = {}) => {
  return request.post("/user/del-corp-group", data);
};
//获取员工分组
api.user_get_staff_list = (data = {}) => {
  return request.get("/user/get-staff-list", {
    params: data,
  });
};
//获取员工分组(新)
api.user_get_simple_staff_list = (data = {}) => {
  return request.get("/user/get-simple-staff-list", {
    params: data,
  });
};
//添加员工到分组/负责人
api.user_add_group_member = (data = {}) => {
  return request.post("/user/add-group-member", data);
};
//从分组移除员工
api.user_remove_staff_group = (data = {}) => {
  return request.post("/user/remove-staff-group", data);
};
//获取员工分组详情
api.user_get_group_by_staff = (data = {}) => {
  return request.post("/user/get-group-by-staff", data);
};
//获取分组带分页
api.user_get_corp_group_list_by_page = (data = {}) => {
  return request.post("/user/get-corp-group-list-by-page", data);
};
//部门列表
api.getDepartmentList = (data = {}) => {
  return request.get("/user/get-department-list", { params: data });
};
//员工分组列表-新
api.getStaffGroupList = (data = {}) => {
  return request.get("/user/get-staff-group-list", { params: data });
};

//获取员工标签
api.bookTagGetLocalTagList = (data = {}) => {
  return request.get("/corp-address-book-tag/get-local-tag-list", { params: data });
};
//新增标签
api.bookTagAddTag = (data = {}) => {
  return request.post("/corp-address-book-tag/add-tag", data);
};
//删除标签
api.bookTagDelTag = (data = {}) => {
  return request.post("/corp-address-book-tag/delete-tag", data);
};
//编辑标签
api.bookTagEditTag = (data = {}) => {
  return request.post("/corp-address-book-tag/edit-tag", data);
};
//标签添加成员
api.bookTagAddStaff = (data = {}) => {
  return request.post("/corp-address-book-tag/add-staff-to-tag", data);
};
//获取标签下员工
api.bookTagGetTagStaff = (data = {}) => {
  return request.get("/corp-address-book-tag/get-local-staff-list-by-tag",{
    params: data
  });
};
//删除标签下员工
api.bookTagDelTagStaff = (data = {}) => {
  return request.post("/corp-address-book-tag/remove-staff-from-tag", data);
};
//获取全部标签和员工
api.bookTagGetAllTagStaff = (data = {}) => {
  return request.get("/corp-address-book-tag/get-tag-staff-list",{
    params: data
  });
};

export default api;
