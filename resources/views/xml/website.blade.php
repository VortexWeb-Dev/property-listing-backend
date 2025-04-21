<?xml version="1.0" encoding="UTF-8"?>
<properties>
@foreach($listings as $listing)
    <property>
        <id>{{ $listing->id }}</id>
        <reference>{{ $listing->reference_no }}</reference>
        <title>{{ $listing->title }}</title>
        <description>{{ strip_tags($listing->description_english) }}</description>
        <type>{{ $listing->property_type }}</type>
        <offer>{{ $listing->offering_type }}</offer>
        <price>{{ $listing->rprice_price }}</price>
        <size>{{ $listing->size }}</size>
        <bedrooms>{{ $listing->bedrooms }}</bedrooms>
        <bathrooms>{{ $listing->bathrooms }}</bathrooms>
        <parking>{{ $listing->parking }}</parking>
        <furnished>{{ $listing->furnished }}</furnished>
        <location>
            <city>{{ $listing->location->city ?? 'Dubai' }}</city>
            <area>{{ $listing->location->area ?? 'Business Bay' }}</area>
            <project>{{ $listing->project_name }}</project>
        </location>
        <agent>
            <name>{{ $listing->agent->name ?? 'Agent' }}</name>
            <email>{{ $listing->agent->email ?? 'agent@example.com' }}</email>
            <phone>{{ $listing->agent->phone ?? '+9710000000' }}</phone>
        </agent>
        <images>
            @foreach(json_decode($listing->photos_urls, true) ?? [] as $img)
                <image>{{ asset($img) }}</image>
            @endforeach
        </images>
        <video>{{ $listing->video_url }}</video>
        <floor_plan>{{ $listing->floor_plan }}</floor_plan>
        <updated>{{ $listing->updated_at->format('Y-m-d H:i:s') }}</updated>
    </property>
@endforeach
</properties>
