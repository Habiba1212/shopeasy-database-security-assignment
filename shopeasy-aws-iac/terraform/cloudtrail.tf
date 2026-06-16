# S3 bucket policy to allow CloudTrail to write logs
resource "aws_s3_bucket_policy" "cloudtrail_bucket_policy" {
  bucket = aws_s3_bucket.shopeasy_logs.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "AWSCloudTrailAclCheck"
        Effect = "Allow"
        Principal = {
          Service = "cloudtrail.amazonaws.com"
        }
        Action   = "s3:GetBucketAcl"
        Resource = aws_s3_bucket.shopeasy_logs.arn
      },
      {
        Sid    = "AWSCloudTrailWrite"
        Effect = "Allow"
        Principal = {
          Service = "cloudtrail.amazonaws.com"
        }
        Action   = "s3:PutObject"
        Resource = "${aws_s3_bucket.shopeasy_logs.arn}/AWSLogs/${data.aws_caller_identity.current.account_id}/*"
        Condition = {
          StringEquals = {
            "s3:x-amz-acl" = "bucket-owner-full-control"
          }
        }
      }
    ]
  })
}

# CloudTrail for AWS activity logging
resource "aws_cloudtrail" "shopeasy_trail" {
  name                          = "${var.project_name}-cloudtrail"
  s3_bucket_name                = aws_s3_bucket.shopeasy_logs.bucket
  kms_key_id                    = aws_kms_key.shopeasy_kms.arn
  include_global_service_events = true
  is_multi_region_trail         = true
  enable_log_file_validation    = true

  depends_on = [
    aws_s3_bucket_policy.cloudtrail_bucket_policy
  ]

  tags = {
    Name = "${var.project_name}-cloudtrail"
  }
}