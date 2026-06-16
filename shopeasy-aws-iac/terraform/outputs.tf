output "vpc_id" {
  description = "ID of the ShopEasy VPC."
  value       = aws_vpc.shopeasy_vpc.id
}

output "public_subnet_az1_id" {
  description = "Public subnet AZ1 ID."
  value       = aws_subnet.public_az1.id
}

output "public_subnet_az2_id" {
  description = "Public subnet AZ2 ID."
  value       = aws_subnet.public_az2.id
}

output "private_app_subnet_az1_id" {
  description = "Private app subnet AZ1 ID."
  value       = aws_subnet.private_app_az1.id
}

output "private_app_subnet_az2_id" {
  description = "Private app subnet AZ2 ID."
  value       = aws_subnet.private_app_az2.id
}

output "private_db_subnet_az1_id" {
  description = "Private database subnet AZ1 ID."
  value       = aws_subnet.private_db_az1.id
}

output "private_db_subnet_az2_id" {
  description = "Private database subnet AZ2 ID."
  value       = aws_subnet.private_db_az2.id
}

output "rds_endpoint" {
  description = "RDS MySQL endpoint for ShopEasy database."
  value       = aws_db_instance.shopeasy_rds.endpoint
}

output "rds_secret_name" {
  description = "Secrets Manager secret name for RDS credentials."
  value       = aws_secretsmanager_secret.rds_credentials.name
}

output "alb_dns_name" {
  description = "Public DNS name of the ShopEasy Application Load Balancer."
  value       = aws_lb.shopeasy_alb.dns_name
}

output "alb_http_url" {
  description = "HTTP URL for testing the ShopEasy ALB."
  value       = "http://${aws_lb.shopeasy_alb.dns_name}"
}

output "alb_https_url" {
  description = "HTTPS URL for testing the ShopEasy ALB."
  value       = "https://${aws_lb.shopeasy_alb.dns_name}"
}