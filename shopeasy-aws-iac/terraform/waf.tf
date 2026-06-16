# AWS WAF Web ACL for ShopEasy public web protection
resource "aws_wafv2_web_acl" "shopeasy_waf" {
  name        = "${var.project_name}-waf"
  description = "WAF Web ACL to protect the ShopEasy public web application"
  scope       = "REGIONAL"

  default_action {
    allow {}
  }

  # AWS managed common protection rules
  rule {
    name     = "AWSManagedRulesCommonRuleSet"
    priority = 1

    override_action {
      none {}
    }

    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesCommonRuleSet"
        vendor_name = "AWS"
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "${var.project_name}-common-rules"
      sampled_requests_enabled   = true
    }
  }

  # AWS managed SQL injection protection rules
  rule {
    name     = "AWSManagedRulesSQLiRuleSet"
    priority = 2

    override_action {
      none {}
    }

    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesSQLiRuleSet"
        vendor_name = "AWS"
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "${var.project_name}-sqli-rules"
      sampled_requests_enabled   = true
    }
  }

  visibility_config {
    cloudwatch_metrics_enabled = true
    metric_name                = "${var.project_name}-waf"
    sampled_requests_enabled   = true
  }

  tags = {
    Name = "${var.project_name}-waf"
  }
}

# Attach WAF Web ACL to the Application Load Balancer
resource "aws_wafv2_web_acl_association" "shopeasy_waf_alb_association" {
  resource_arn = aws_lb.shopeasy_alb.arn
  web_acl_arn  = aws_wafv2_web_acl.shopeasy_waf.arn
}