# Get AWS account ID to make the S3 bucket name unique
data "aws_caller_identity" "current" {}

# KMS key for encrypting S3 logs/backups
resource "aws_kms_key" "shopeasy_kms" {
  description             = "KMS key for ShopEasy encrypted resources"
  deletion_window_in_days = 7
  enable_key_rotation     = true

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "EnableRootPermissions"
        Effect = "Allow"
        Principal = {
          AWS = "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"
        }
        Action   = "kms:*"
        Resource = "*"
      },
      {
        Sid    = "AllowCloudTrailToUseKMS"
        Effect = "Allow"
        Principal = {
          Service = "cloudtrail.amazonaws.com"
        }
        Action = [
          "kms:GenerateDataKey*",
          "kms:DescribeKey"
        ]
        Resource = "*"
        Condition = {
          StringLike = {
            "kms:EncryptionContext:aws:cloudtrail:arn" = "arn:aws:cloudtrail:*:${data.aws_caller_identity.current.account_id}:trail/*"
          }
        }
      }
    ]
  })

  tags = {
    Name = "${var.project_name}-kms-key"
  }
}

resource "aws_kms_alias" "shopeasy_kms_alias" {
  name          = "alias/${var.project_name}-kms"
  target_key_id = aws_kms_key.shopeasy_kms.key_id
}

# S3 bucket for logs and backups
resource "aws_s3_bucket" "shopeasy_logs" {
  bucket = "${var.project_name}-logs-${data.aws_caller_identity.current.account_id}-${var.aws_region}"

  tags = {
    Name = "${var.project_name}-logs-backups-bucket"
  }
}

# Block public access to the S3 bucket
resource "aws_s3_bucket_public_access_block" "shopeasy_logs_block" {
  bucket = aws_s3_bucket.shopeasy_logs.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# Enable S3 bucket encryption using KMS
resource "aws_s3_bucket_server_side_encryption_configuration" "shopeasy_logs_encryption" {
  bucket = aws_s3_bucket.shopeasy_logs.id

  rule {
    apply_server_side_encryption_by_default {
      kms_master_key_id = aws_kms_key.shopeasy_kms.arn
      sse_algorithm     = "aws:kms"
    }
  }
}

# Enable versioning for backup protection
resource "aws_s3_bucket_versioning" "shopeasy_logs_versioning" {
  bucket = aws_s3_bucket.shopeasy_logs.id

  versioning_configuration {
    status = "Enabled"
  }
}