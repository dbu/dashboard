var x = {
    "size": 5000,
    "sort": {"owner_login": {"order": "asc"}},
    "facets": {
        "Milestones": {"terms": {"field": "milestone_title", "size": 3, "order": "count"}},
        "Author": {"terms": {"field": "user_login", "size": 10, "order": "count"}},
        "Assignee": {"terms": {"field": "assignee_login", "size": 10, "order": "count"}}
    },
    "query": {
        "filtered": {
            "query": {"match_all": {}},
            "filter": {
                "and": [
                    {"type": {"value": "github_repository"}},
                    {"or": [
                        {"terms": {"full_name": ["doctrine\/phpcr-odm", "doctrine\/DoctrinePHPCRBundle", "doctrine\/phpcr-odm-documentation", "sonata-project\/SonataDoctrinePhpcrAdminBundle"]}},
                        {"and": [
                            {"terms": {"owner_login": ["phpcr", "jackalope", "symfony-cmf"]}},
                            {"not": {"filter": {"terms": {"name": ["jr_cr", "jr_cr_demo", "jackalope-mongodb", "create_jackrabbit4jackalope", "jackrabbit", "createphp"]}}}}
                        ]}
                    ]}
                ]
            }
        }
    }
};

 curl -XGET http://localhost:9200/_all/_search?pretty=1 -d '{
    "query": {
        "filtered": {
            "query": {match_all: {}},
            "filter": {
                "type": {
                    "value": "github_issue"
                }
            }
        }
    },
    "filter": {
        "has_parent": {
            "query": {        "match_all": {}
            },
            "type":"github_repository"
        }
    }
}'
