// 匹配常用字符集 列表
// 根据业务需求 配置字符集
const characterList = {
    "<": "&lt;",
    ">": "&gt;",
    "&": "&amp;",
    "¥": "&yen;",
};
// 处理函数
const nodeChild = (node, val) => {
    const children = Array.from(node.children);
    let nodeSplicing = "";
    //   判断是否为末级节点
    if (children.length !== 0) {
        // 子节点数据遍历
        children.forEach((item) => {
            // 递归 子节点
            nodeChild(item, val);
            // nodeSplicing 为当前节点的子级拼接后的字符串
            nodeSplicing = nodeSplicing + item.outerHTML;
        });
        // 进行数据替换 防止标签内部 有需要标记的重复数据 导致标红后 标签外露
        // innerHTML 返回标签开始和结束标签之间的HTML
        node.innerHTML = node.innerHTML
            .replaceAll(nodeSplicing, `*^(_)`)
            .toString();
        node.innerHTML = node.innerHTML.replaceAll(
            val,
            `<span style="color:red">${val}</span>`
        );
        node.innerHTML = node.innerHTML.replaceAll("*^(_)", nodeSplicing);
    } else {
        // 末级节点 不需要内部标签数据替换
        node.innerHTML = node.innerHTML.replaceAll(
            val,
            `<span style="color:red">${val}</span>`
        );
    }
};
export default {
    mounted (el, binding) {
        // 进行字符集匹配
        const value = binding.value;
        const valueArr = value.split("");
        valueArr.forEach((item, index) => {
            valueArr[index] = characterList[item] || item;
        });
        // 进行数据匹配 如当前绑定节点 整体并无需要标记内容 不进行DOM 操作
        const idx = el.innerHTML.indexOf(valueArr.join(""));
        if (idx != -1) {
            nodeChild(el, valueArr.join(""));
        }
    },
    //   binding 数据改变 调用 初始不会调用
    //   不建议 因为原始DOM已经被标记
    //   可根据业务需要进行 原始数据传参 对原始数据进行操作 然后进行DOM替换即可
    //   beforeUpdate(el, binding?) {
    //     nodeChild(el, binding);
    //   },
};
