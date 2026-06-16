variable "aws_region" {
  description = "AWS region for the ShopEasy AWS infrastructure."
  type        = string
  default     = "us-east-1"
}

variable "project_name" {
  description = "Project name used for AWS resource names."
  type        = string
  default     = "shopeasy"
}

variable "db_name" {
  description = "ShopEasy database name."
  type        = string
  default     = "shopeasy_db"
}

variable "db_username" {
  description = "RDS master username."
  type        = string
  default     = "shopeasyadmin"
}

variable "db_password" {
  description = "RDS master password."
  type        = string
  sensitive   = true
}