this["lpa"] = this["lpa"] || {};
this["lpa"]["templates"] = this["lpa"]["templates"] || {};

this["lpa"]["templates"]["dialog.confirmRepeatApplication"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, helper, functionType="function", escapeExpression=this.escapeExpression;


  buffer += "<div class=\"dialog__container\">\n\n    <div class=\"dialog__title-block\">";
  if (helper = helpers.dialogTitle) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.dialogTitle); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</div>\n\n    <div class=\"dialog__message-block\"><p>";
  if (helper = helpers.dialogMessage) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.dialogMessage); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</p></div>\n\n    <div class=\"dialog__button-bar\">\n        <a class=\"button dialog__button--accept ";
  if (helper = helpers.acceptClass) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.acceptClass); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">";
  if (helper = helpers.acceptButtonText) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.acceptButtonText); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</a>\n        <a class=\"button-secondary dialog__button--cancel  ";
  if (helper = helpers.cancelClass) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.cancelClass); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">";
  if (helper = helpers.cancelButtonText) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.cancelButtonText); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</a>\n    </div>\n\n</div>\n";
  return buffer;
  });

this["lpa"]["templates"]["errors.formElement"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, helper, functionType="function", escapeExpression=this.escapeExpression;


  buffer += "<div class=\"form-element-errors\">\n    <div class=\"group validation\">\n        <span class=\"validation-message\">";
  if (helper = helpers.validationMessage) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.validationMessage); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</span>\n    </div>\n</div>";
  return buffer;
  });

this["lpa"]["templates"]["errors.formSummary"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div class=\"validation-summary group\" role=\"alert\" aria-labelledby=\"error-heading\" tabindex=\"-1\">\n    <h1 id=\"error-heading\">There was a problem submitting the form</h1>\n\n    <p>Because of the following problems:</p>\n    <ol>\n    </ol>\n</div>\n";
  });

this["lpa"]["templates"]["input.checkbox"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, helper, functionType="function", escapeExpression=this.escapeExpression;


  buffer += "<fieldset class=\"";
  if (helper = helpers.elementJSref) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementJSref); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">\n    <div class=\"input-checkbox group\">\n        <label for=\"";
  if (helper = helpers.elementName) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementName); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">\n            <input type=\"checkbox\" name=\"";
  if (helper = helpers.elementName) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementName); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" id=\"";
  if (helper = helpers.elementName) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementName); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" class=\"confirmation-validation\" value=\"1\"\n                   required=\"required\">\n            ";
  if (helper = helpers.elementLabel) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementLabel); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n        </label>\n    </div>\n</fieldset>\n";
  return buffer;
  });

this["lpa"]["templates"]["popup.close"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<p class=\"close\">\n  <a href=\"#\" class=\"js-popup-close\" title=\"Click or press escape to close this window\">Close</a>\n</p>";
  });

this["lpa"]["templates"]["popup.container"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div id=\"popup\" role=\"dialog\"></div>";
  });

this["lpa"]["templates"]["popup.content"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div id=\"popup-content\"></div>";
  });

this["lpa"]["templates"]["popup.mask"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div id=\"mask\" class=\"popover-mask\"></div>";
  });

this["lpa"]["templates"]["postcodeLookup.address-toggle"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, self=this;

function program1(depth0,data) {
  
  
  return "\n  <li><a href=\"#\" data-address-type=\"postal\" class=\"js-PostcodeLookup__toggle-address\">Enter address manually</a></li>\n  ";
  }

function program3(depth0,data) {
  
  
  return "\n  <li><a href=\"#\" data-address-type=\"dx\" class=\"js-PostcodeLookup__toggle-address\">Enter DX address</a></li>\n  ";
  }

  buffer += "<ul class=\"address-type-toggle\">\n  ";
  stack1 = helpers['if'].call(depth0, (depth0 && depth0.postal), {hash:{},inverse:self.noop,fn:self.program(1, program1, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n  ";
  stack1 = helpers['if'].call(depth0, (depth0 && depth0.dx), {hash:{},inverse:self.noop,fn:self.program(3, program3, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n</ul>";
  return buffer;
  });

this["lpa"]["templates"]["postcodeLookup.search-field"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<p class=\"group js-PostcodeLookup__search\">\n  <label for=\"postcode-lookup\">Postcode</label>\n  <input autocomplete=\"off\" type=\"text\" id=\"postcode-lookup\" class=\"postcode js-PostcodeLookup__query\">\n  <a href=\"#\" id=\"find_uk_address\" class=\"postcode-lookup button-secondary js-PostcodeLookup__search-btn\" role=\"button\">Find UK address</a>\n</p>";
  });

this["lpa"]["templates"]["postcodeLookup.search-result"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, functionType="function", escapeExpression=this.escapeExpression, self=this;

function program1(depth0,data) {
  
  var buffer = "", stack1, helper;
  buffer += "\n      <option value=\"";
  if (helper = helpers.id) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.id); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" data-line1=\"";
  if (helper = helpers.line1) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.line1); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" data-line2=\"";
  if (helper = helpers.line2) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.line2); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" data-line3=\"";
  if (helper = helpers.line3) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.line3); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" data-postcode=\"";
  if (helper = helpers.postcode) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.postcode); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">";
  if (helper = helpers.description) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.description); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</option>\n    ";
  return buffer;
  }

  buffer += "<div class=\"group\">\n  <label for=\"address-search-result\">Address</label>\n  <select class=\"js-PostcodeLookup__search-results\" id=\"address-search-result\">\n    <option value=\"\">Please select an address...</option>\n    ";
  stack1 = helpers.each.call(depth0, (depth0 && depth0.results), {hash:{},inverse:self.noop,fn:self.program(1, program1, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n  </select>\n</div>\n";
  return buffer;
  });

this["lpa"]["templates"]["shared.loading-popup"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div class=\"loading\">\n  <p><img src=\"/assets/v1/images/ajax/ajax-loader.gif\" class=\"spinner\"> Loading</p>\n</div>\n";
  });