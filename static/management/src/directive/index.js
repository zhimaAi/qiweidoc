import emphasize from "./modules/emphasize";
// 统一入口
export default function directive(app) {
  app.directive("emphasize", emphasize);
}
