# CloudWatch log group for ShopEasy application logs
resource "aws_cloudwatch_log_group" "shopeasy_app_logs" {
  name              = "/shopeasy/application"
  retention_in_days = 30

  tags = {
    Name = "${var.project_name}-application-logs"
  }
}

# CloudWatch alarm for EC2 CPU monitoring
resource "aws_cloudwatch_metric_alarm" "ec2_high_cpu" {
  alarm_name          = "${var.project_name}-ec2-high-cpu"
  alarm_description   = "Alarm when EC2 CPU utilization is higher than 80 percent"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "CPUUtilization"
  namespace           = "AWS/EC2"
  period              = 300
  statistic           = "Average"
  threshold           = 80
  treat_missing_data  = "notBreaching"

  tags = {
    Name = "${var.project_name}-ec2-high-cpu-alarm"
  }
}