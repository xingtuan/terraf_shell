<p>A new B2B lead has been submitted.</p>
<p><strong>Reference:</strong> {{ $lead->reference ?: sprintf('INQ-%06d', $lead->id) }}</p>
<p><strong>Type:</strong> {{ $lead->inquiry_type }}</p>
<p><strong>Name:</strong> {{ $lead->name }}</p>
<p><strong>Company:</strong> {{ $lead->company_name }}</p>
<p><strong>Email:</strong> {{ $lead->email }}</p>
<p><strong>Source:</strong> {{ $lead->source_page ?? 'N/A' }}</p>
<p><strong>Message:</strong></p>
<p>{{ $lead->message }}</p>
